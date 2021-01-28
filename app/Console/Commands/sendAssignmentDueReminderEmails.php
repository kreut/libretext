<?php

namespace App\Console\Commands;

use App\Email;
use App\Mail\AssignmentDueReminder;
use Illuminate\Console\Command;

class sendAssignmentDueReminderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:sendAssignmentDueReminderEmails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends all reminder emails to students';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $email = new Email();
        $email->sendAssignmentDueReminders();
    }
}
