<?php

namespace Tests\Unit\Support;

use App\Support\StudentNotificationPresenter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentNotificationPresenterTest extends TestCase
{
    #[DataProvider('relativeUrlProvider')]
    public function test_relative_url_normalizes_absolute_app_urls(string $input, ?string $expected): void
    {
        $this->assertSame($expected, StudentNotificationPresenter::relativeUrl($input));
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function relativeUrlProvider(): array
    {
        return [
            'absolute url' => [
                'https://bilingualvitae.test/student/study-bank',
                '/student/study-bank',
            ],
            'already relative' => [
                '/student/study-bank',
                '/student/study-bank',
            ],
            'with query string' => [
                'http://localhost/student/assignments/1?tab=work',
                '/student/assignments/1?tab=work',
            ],
            'empty string' => [
                '',
                null,
            ],
        ];
    }
}
