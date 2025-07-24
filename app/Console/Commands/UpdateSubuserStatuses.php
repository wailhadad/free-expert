<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Http\Helpers\UserPermissionHelper;

class UpdateSubuserStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subusers:update-statuses {--user-id= : Update statuses for a specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update subuser statuses for all users based on their current package limits';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        
        if ($userId) {
            // Update for specific user
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found!");
                return 1;
            }
            
            $this->info("Updating subuser statuses for user: {$user->username} (ID: {$user->id})");
            $this->updateUserSubuserStatuses($user);
            $this->info("Completed updating subuser statuses for user: {$user->username}");
            
        } else {
            // Update for all users
            $this->info("Updating subuser statuses for all users...");
            
            $users = User::whereHas('subusers')->get();
            $totalUsers = $users->count();
            
            if ($totalUsers === 0) {
                $this->info("No users with subusers found.");
                return 0;
            }
            
            $this->info("Found {$totalUsers} users with subusers.");
            
            $progressBar = $this->output->createProgressBar($totalUsers);
            $progressBar->start();
            
            $updatedCount = 0;
            $errorCount = 0;
            
            foreach ($users as $user) {
                try {
                    $this->updateUserSubuserStatuses($user);
                    $updatedCount++;
                } catch (\Exception $e) {
                    $this->error("\nError updating user {$user->username}: " . $e->getMessage());
                    $errorCount++;
                }
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine();
            
            $this->info("Completed updating subuser statuses!");
            $this->info("Successfully updated: {$updatedCount} users");
            if ($errorCount > 0) {
                $this->warn("Errors encountered: {$errorCount} users");
            }
        }
        
        return 0;
    }
    
    /**
     * Update subuser statuses for a specific user
     */
    private function updateUserSubuserStatuses(User $user)
    {
        $totalMaxSubusers = UserPermissionHelper::totalMaxSubusers($user->id);
        $actualSubusersCount = $user->subusers()->count();
        
        if ($totalMaxSubusers >= $actualSubusersCount) {
            // All subusers can be active
            $user->subusers()->update(['status' => true]);
            $this->line("  - User {$user->username}: All {$actualSubusersCount} subusers activated (limit: {$totalMaxSubusers})");
        } else {
            // Need to prioritize subusers
            $prioritizedSubusers = UserPermissionHelper::getPrioritizedSubusers($user->id, $totalMaxSubusers);
            $activeCount = $prioritizedSubusers->count();
            $inactiveCount = $actualSubusersCount - $activeCount;
            
            $this->line("  - User {$user->username}: {$activeCount} subusers activated, {$inactiveCount} deactivated (limit: {$totalMaxSubusers})");
        }
    }
} 