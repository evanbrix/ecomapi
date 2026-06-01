<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $keys = [
            'site_name',
            'site_tagline',
            'logo',
            'favicon',
            'og_image',
            'contact_email',
            'contact_phone',
            'address',
            'facebook_url',
            'instagram_url',
            'twitter_url',
        ];

        foreach ($keys as $key) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => null]);
        }
    }
}
