<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '指定されたメールアドレスのユーザーを管理者に設定します';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("メールアドレス '{$email}' のユーザーが見つかりません。");
            return 1;
        }
        
        $user->role = 'admin';
        $user->save();
        
        $this->info("ユーザー '{$user->name}' ({$email}) を管理者に設定しました。");
        return 0;
    }
}

