<?php

namespace App\Filament\Resources\Tracks\Schemas;

use App\Models\Track;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TrackForm
{
    public static function configure(Schema $schema): Schema
    {
        $hasDuplicates = $hasOriginals = false;

        if($schema->model instanceof Track)
        {
            $hasDuplicates = $schema->model->duplicates()->exists();
            $hasOriginals = $schema->model->originals()->exists();
        }

        return $schema
            ->components([
                FileUpload::make('attachment')
                    ->disk('media')
                    ->acceptedFileTypes([
                        'audio/mpeg',
                        'audio/ogg',
                        'audio/flac'
                    ])
                    ->preserveFilenames()
                    ->hiddenOn([Operation::View, Operation::Edit])
                    ->required()
                    ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): string {

                        $storeMethod = $component->getVisibility() === 'public' ? 'storePubliclyAs' : 'storeAs';

                        $filename = $file->getClientOriginalName();

                        if(Storage::disk('media')->exists($filename))
                        {
                            $suffixNumber = 0;

                            do{

                                $suffixNumber++;
                                $modifiedFilename = "$filename ($suffixNumber)";

                            }while(Storage::disk('media')->exists($modifiedFilename));

                            $filename = $modifiedFilename;
                        }

                        return $file->{$storeMethod}($component->getDirectory(), $filename, $component->getDiskName());

                    })
                    ,
                TextInput::make('title')
                    ->hiddenOn(Operation::Create),
                TextInput::make('artist')
                    ->hiddenOn(Operation::Create),
                TextEntry::make('path')
                    ->label('Path')
                    ->hiddenOn([Operation::Create]),
                ViewField::make('audio')
                    ->view('filament.forms.fields.audio-player')
                    ->hiddenOn([Operation::Create]),
                ViewField::make('duplicates')
                    ->view('filament.forms.fields.track-duplicates')
                    ->when($hasDuplicates)
                    ->hiddenOn([Operation::Create]),
                ViewField::make('originals')
                    ->view('filament.forms.fields.track-duplicates')
                    ->when($hasOriginals)
                    ->hiddenOn([Operation::Create]),
            ]);
    }
}
