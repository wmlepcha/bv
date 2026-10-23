<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationFeePaidVia;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\RegistrationActivated;
use App\Support\PaymentInvoiceSignedUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaymentInvoiceDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_student_can_download_own_invoice(): void
    {
        $student = User::factory()->student()->paid()->create();
        $payment = $this->createPaidPayment($student);

        $response = $this->actingAs($student)->get(route('student.payments.invoice', $payment));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader(
            'content-disposition',
            'attachment; filename="payment-slip-'.$this->invoiceFilename($payment).'"',
        );
    }

    public function test_student_cannot_download_another_students_invoice(): void
    {
        $student = User::factory()->student()->paid()->create();
        $otherStudent = User::factory()->student()->paid()->create();
        $payment = $this->createPaidPayment($otherStudent);

        $this->actingAs($student)
            ->get(route('student.payments.invoice', $payment))
            ->assertForbidden();
    }

    public function test_student_cannot_download_pending_payment_invoice(): void
    {
        $student = User::factory()->student()->paid()->create();
        $payment = Payment::query()->create([
            'user_id' => $student->id,
            'gateway_order_id' => 'order_pending',
            'amount' => 590000,
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($student)
            ->get(route('student.payments.invoice', $payment))
            ->assertNotFound();
    }

    public function test_guest_cannot_download_student_invoice(): void
    {
        $student = User::factory()->student()->paid()->create();
        $payment = $this->createPaidPayment($student);

        $this->get(route('student.payments.invoice', $payment))
            ->assertRedirect();
    }

    public function test_unpaid_student_cannot_download_invoice(): void
    {
        $student = User::factory()->student()->unpaid()->create();
        $payment = $this->createPaidPayment($student);

        $this->actingAs($student)
            ->get(route('student.payments.invoice', $payment))
            ->assertForbidden();
    }

    public function test_signed_invoice_url_downloads_pdf_without_login(): void
    {
        $student = User::factory()->student()->paid()->create();
        $payment = $this->createPaidPayment($student);

        $url = PaymentInvoiceSignedUrl::for($payment);
        $this->assertNotNull($url);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_expired_signed_invoice_url_is_rejected(): void
    {
        $student = User::factory()->student()->paid()->create();
        $payment = $this->createPaidPayment($student);

        $url = URL::temporarySignedRoute(
            'invoices.download',
            now()->subMinute(),
            ['payment' => $payment->id],
        );

        $this->get($url)->assertForbidden();
    }

    public function test_tampered_signed_invoice_url_is_rejected(): void
    {
        $student = User::factory()->student()->paid()->create();
        $payment = $this->createPaidPayment($student);

        $url = PaymentInvoiceSignedUrl::for($payment);
        $this->assertNotNull($url);

        $tamperedUrl = preg_replace('/signature=[^&]+/', 'signature=invalid', $url) ?? $url;

        $this->get($tamperedUrl)->assertForbidden();
    }

    public function test_signed_invoice_url_rejects_unpaid_payment(): void
    {
        $student = User::factory()->student()->paid()->create();
        $payment = Payment::query()->create([
            'user_id' => $student->id,
            'gateway_order_id' => 'order_unsigned_pending',
            'amount' => 590000,
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
        ]);

        $url = URL::temporarySignedRoute(
            'invoices.download',
            now()->addHour(),
            ['payment' => $payment->id],
        );

        $this->get($url)->assertNotFound();
    }

    public function test_registration_activated_includes_invoice_metadata_when_payment_present(): void
    {
        $student = User::factory()->student()->paid()->create();
        $payment = $this->createPaidPayment($student);

        $notification = new RegistrationActivated(
            RegistrationFeePaidVia::Cashfree,
            $payment->id,
            false,
            now()->addYear()->toIso8601String(),
        );

        $payload = $notification->toArray($student);

        $this->assertSame($payment->invoice_number, $payload['invoice_number']);
        $this->assertSame('₹5,900.00', $payload['amount_label']);
        $this->assertNotNull($payload['invoice_url']);
        $this->assertStringContainsString('signature=', (string) $payload['invoice_url']);
    }

    public function test_registration_activated_whatsapp_uses_registration_template_with_pdf_path(): void
    {
        config([
            'services.whatsapp.twilio.templates.registration' => 'HXregistration',
            'services.whatsapp.twilio.templates.update' => 'HXupdate',
        ]);

        $student = User::factory()->student()->paid()->create(['name' => 'Rahul Sharma']);
        $payment = $this->createPaidPayment($student);

        $notification = new RegistrationActivated(
            RegistrationFeePaidVia::Cashfree,
            $payment->id,
            false,
            now()->addYear()->toIso8601String(),
        );

        $content = $notification->toWhatsAppContent($student);

        $this->assertNotNull($content);
        $this->assertSame('HXregistration', $content->contentSid);
        $this->assertSame('Rahul Sharma', $content->variables['1']);
        $this->assertSame('INR 5900.00', $content->variables['2']);
        $this->assertSame($payment->invoice_number, $content->variables['3']);
        $this->assertSame('storage/whatsapp-invoices/'.$payment->id.'/payment-slip.pdf', $content->variables['6']);
        $this->assertTrue(
            Storage::disk('public')->exists(
                'whatsapp-invoices/'.$payment->id.'/payment-slip.pdf',
            ),
        );
    }

    public function test_registration_activated_without_payment_uses_update_template(): void
    {
        config([
            'services.whatsapp.twilio.templates.registration' => 'HXregistration',
            'services.whatsapp.twilio.templates.update' => 'HXupdate',
        ]);

        $student = User::factory()->student()->paid()->create(['name' => 'Rahul Sharma']);

        $notification = new RegistrationActivated(
            RegistrationFeePaidVia::ActivationCode,
            null,
            false,
            now()->addYear()->toIso8601String(),
        );

        $content = $notification->toWhatsAppContent($student);

        $this->assertNotNull($content);
        $this->assertSame('HXupdate', $content->contentSid);
        $this->assertSame('Rahul Sharma', $content->variables['1']);
        $this->assertArrayHasKey('2', $content->variables);
        $this->assertArrayHasKey('3', $content->variables);
    }

    private function createPaidPayment(User $student): Payment
    {
        $payment = Payment::query()->create([
            'user_id' => $student->id,
            'gateway_order_id' => 'order_paid_'.$student->id,
            'gateway_payment_id' => 'pay_paid_'.$student->id,
            'amount' => 590000,
            'currency' => 'INR',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $payment->update([
            'invoice_number' => 'BV/REG-SUB/'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT),
        ]);

        return $payment->fresh();
    }

    private function invoiceFilename(Payment $payment): string
    {
        return str_replace('/', '-', (string) $payment->invoice_number).'.pdf';
    }
}
