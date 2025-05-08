<?php

namespace Database\Seeders;

use App\Models\Bot;
use App\Models\File;
use App\Models\User;
use App\Models\Vc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample file types and sizes
        $fileTypes = [
            'pdf' => [100000, 2000000],
            'docx' => [50000, 500000],
            'txt' => [1000, 50000],
            'csv' => [5000, 200000],
            'json' => [3000, 150000]
        ];
        
        // Sample file names
        $fileNames = [
            'product_documentation',
            'user_manual',
            'api_reference',
            'technical_specification',
            'knowledge_base',
            'faq_document',
            'training_material',
            'configuration_guide',
            'system_architecture',
            'data_dictionary'
        ];
        
        // Get all VCs
        $vcs = Vc::all();
        
        foreach ($vcs as $vc) {
            $bot = Bot::find($vc->bot_id);
            $user = User::find($vc->user_id);
            
            // Create 2-5 files per VC
            $numFiles = rand(2, 5);
            $createdFiles = [];
            
            for ($i = 0; $i < $numFiles; $i++) {
                // Select random file type and name
                $fileType = array_rand($fileTypes);
                $fileName = $fileNames[array_rand($fileNames)];
                
                // Generate random file size within range for the selected type
                $fileSize = rand($fileTypes[$fileType][0], $fileTypes[$fileType][1]);
                
                // Create unique file name
                $uniqueFileName = $fileName . '_' . strtolower(Str::random(5)) . '.' . $fileType;
                
                // Create the file
                $file = File::create([
                    'bot_id' => $bot->id,
                    'user_id' => $user->id,
                    'file_id' => 'file_' . Str::uuid(),
                    'file_name' => $uniqueFileName,
                    'bytes' => (string)$fileSize,
                ]);
                
                $createdFiles[] = $file;
            }
            
            // Attach all created files to the VC
            $vc->files()->attach(collect($createdFiles)->pluck('id'));
        }
    }
}
