<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
      $this->call(AddressSeeder::class);
      $this->call(ChurchSeeder::class);
      $this->call(EventSeeder::class);
      $this->call(HeirachySeeder::class);
      $this->call(HeirachyGroupSeeder::class);
    }
}
