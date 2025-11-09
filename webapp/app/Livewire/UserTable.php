<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;

class UserTable extends Component
{
    public function render()
    {
        // 全ユーザーを取得
        $users = User::all();

        return view('livewire.user-table', [
            'users' => $users,
        ]);
    }
}
