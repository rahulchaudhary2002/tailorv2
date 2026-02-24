<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Seeder;

class OutletUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->get(['id']);
        $outlets = Outlet::get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($outlets as $outlet) {
            $outlet->users()->syncWithoutDetaching($users->pluck('id')->all());
        }
    }
}
