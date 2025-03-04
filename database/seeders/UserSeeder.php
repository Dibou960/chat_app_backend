<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder {
    /**
    * Run the database seeds.
    *
    * @return void
    */

    public function run() {
        $users = [
            // [
            //     'name' => 'Utilisateur 1',
            //     'email' => 'user1@example.com',
            //     'password' => Hash::make( 'password123' ),
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // 'url_photo'=>'teste.jpg',
            // ],
            // [
            //     'name' => 'Utilisateur 2',
            //     'email' => 'user2@example.com',
            //     'password' => Hash::make( 'password123' ),
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // 'url_photo'=>'teste.jpg',
            // ],
            // [
            //     'name' => 'Utilisateur 3',
            //     'email' => 'user3@example.com',
            //     'password' => Hash::make( 'password123' ),
            //     'created_at' => Carbon::now(),
            //     'updated_at' => Carbon::now(),
            // 'url_photo'=>'teste.jpg',
            // ],
            [
                'name' => 'Utilisateur 4',
                'email' => 'user4@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 5',
                'email' => 'user5@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 6',
                'email' => 'user6@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 7',
                'email' => 'user7@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 8',
                'email' => 'user8@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 9',
                'email' => 'user9@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 10',
                'email' => 'user10@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 11',
                'email' => 'user11@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 12',
                'email' => 'user12@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 13',
                'email' => 'user13@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 14',
                'email' => 'user14@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
            [
                'name' => 'Utilisateur 15',
                'email' => 'user15@example.com',
                'password' => Hash::make( 'password123' ),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'url_photo'=>'teste.jpg',
            ],
        ];

        DB::table( 'users' )->insert( $users );
    }
}
