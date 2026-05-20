<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Models\Lead;

class SendLeadInquiryEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $lead;

    /**
     * Create a new job instance.
     */
    public function __construct(Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $adminEmail = 'infoblackwolvesacc@blackwolvesacquisitionllc.com';
        $html = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #c5a059; border-radius: 15px;'>
                <h2 style='color: #05101c; border-bottom: 2px solid #c5a059; padding-bottom: 10px;'>New Contact Inquiry Received</h2>
                <p style='margin: 10px 0;'><strong>Name:</strong> {$this->lead->name}</p>
                <p style='margin: 10px 0;'><strong>Email:</strong> {$this->lead->email}</p>
                <p style='margin: 10px 0;'><strong>Phone:</strong> " . ($this->lead->phone ?? 'N/A') . "</p>
                <p style='margin: 20px 0 5px 0;'><strong>Inquiry Details:</strong></p>
                <div style='background: #f9f7f2; padding: 15px; border-left: 4px solid #c5a059; border-radius: 5px; color: #333;'>
                    " . nl2br(e($this->lead->purpose)) . "
                </div>
                <p style='font-size: 11px; color: #888; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px;'>
                    Sent from Black Wolves Acquisition LLC System Protocol.
                </p>
            </div>
        ";
        
        Mail::html($html, function ($message) use ($adminEmail) {
            $message->to($adminEmail)
                ->subject("New Inquiry: {$this->lead->name} - Black Wolves Acquisition");
        });
    }
}
