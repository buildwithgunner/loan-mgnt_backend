<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Fix & Flip', 'New Construction', 'Cash-Out Refinance', 'Bridge Loan', 'Commercial Real Estate'];
        $statuses = ['pending', 'under_review', 'approved', 'rejected'];
        $stages = [
            'Application Submitted', 
            'Document Verification', 
            'Background Check', 
            'Appraisal Ordered', 
            'Underwriting Review', 
            'Final Approval Issued'
        ];

        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement($types),
            'property' => $this->faker->address(),
            'amount' => $this->faker->numberBetween(100000, 2000000),
            'status' => $this->faker->randomElement($statuses),
            'ltv' => $this->faker->numberBetween(65, 85),
            'processing_stage' => $this->faker->randomElement($stages),
            'processing_level' => $this->faker->numberBetween(10, 90),
            'approval_code' => null,
            'form_data' => [
                'ssn' => 'XXX-XX-' . $this->faker->numberBetween(1000, 9999),
                'dobMonth' => $this->faker->numberBetween(1, 12),
                'dobDay' => $this->faker->numberBetween(1, 28),
                'dobYear' => $this->faker->numberBetween(1960, 2002),
                'occupation' => $this->faker->jobTitle(),
                'address' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->stateAbbr(),
                'zipCode' => $this->faker->postcode(),
                'estimatedFico' => $this->faker->numberBetween(620, 850),
                'estimatedNetWorth' => '$' . number_format($this->faker->numberBetween(50000, 5000000)),
                'hasCoBorrower' => $this->faker->randomElement(['yes', 'no']),
                'purpose' => $this->faker->sentence(),
                'purchasePrice' => '$' . number_format($this->faker->numberBetween(150000, 3000000)),
                'loanDuration' => $this->faker->numberBetween(6, 36) . ' Months',
            ]
        ];
    }
}
