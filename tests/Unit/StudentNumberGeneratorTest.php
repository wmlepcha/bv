<?php

namespace Tests\Unit;

use App\Enums\SpecialId;
use App\Models\User;
use App\Services\StudentNumberGenerator;
use App\Support\IndianStates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_candidate_id_with_padded_sequence(): void
    {
        $id = app(StudentNumberGenerator::class)->generate('SK', SpecialId::Coi, 2024);

        $this->assertSame('SK-COI-2024-01', $id);
    }

    public function test_increments_sequence_per_state_special_and_year(): void
    {
        User::factory()->unpaid()->create([
            'student_number' => 'SK-COI-2024-01',
            'state_code' => 'SK',
            'special_id' => SpecialId::Coi,
            'registration_year' => 2024,
        ]);

        $generator = app(StudentNumberGenerator::class);

        $this->assertSame('SK-COI-2024-02', $generator->generate('SK', SpecialId::Coi, 2024));
        $this->assertSame('SK-RC-2024-01', $generator->generate('SK', SpecialId::Rc, 2024));
        $this->assertSame('MH-COI-2024-01', $generator->generate('MH', SpecialId::Coi, 2024));
        $this->assertSame('SK-COI-2025-01', $generator->generate('SK', SpecialId::Coi, 2025));
    }

    public function test_indian_states_map_sikkim(): void
    {
        $this->assertSame('SK', IndianStates::codeForName('Sikkim'));
        $this->assertSame('SK', IndianStates::codeForName('sikkim'));
        $this->assertSame('DL', IndianStates::codeForName('Delhi'));
        $this->assertNull(IndianStates::codeForName('Not A State'));
    }
}
