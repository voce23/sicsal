<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── Columna principal (8/12) ──
            Section::make('Contenido del artículo')
                ->columnSpan(2)
                ->schema([
                    TextInput::make('titulo')
                        ->label('Título')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if (! $get('slug')) {
                                $set('slug', Post::generarSlug($state));
                            }
                        })
                        ->columnSpanFull(),

                    Textarea::make('extracto')
                        ->label('Extracto / Resumen')
                        ->helperText('Breve descripción visible en la lista del blog (máx. 300 caracteres).')
                        ->maxLength(300)
                        ->rows(3)
                        ->columnSpanFull(),

                    // ── Editor dual: Visual | HTML | Vista previa ──
                    Tabs::make('editor_tabs')
                        ->tabs([
                            Tab::make('✏️  Visual')
                                ->schema([
                                    RichEditor::make('contenido')
                                        ->label(false)
                                        ->toolbarButtons([
                                            'bold', 'italic', 'underline', 'strike',
                                            'h2', 'h3',
                                            'link', 'blockquote', 'codeBlock',
                                            'bulletList', 'orderedList',
                                            'attachFiles',
                                        ])
                                        ->fileAttachmentsDirectory('blog/adjuntos')
                                        ->columnSpanFull(),
                                ]),

                            Tab::make('◇  HTML')
                                ->schema([
                                    Textarea::make('contenido')
                                        ->label(false)
                                        ->rows(20)
                                        ->extraAttributes(['class' => 'font-mono text-xs', 'spellcheck' => 'false'])
                                        ->columnSpanFull(),
                                ]),

                            Tab::make('👁  Vista Previa')
                                ->schema([
                                    Placeholder::make('vista_previa')
                                        ->label(false)
                                        ->content(fn ($get) => new HtmlString(
                                            '<div class="prose prose-sm max-w-none min-h-[200px] rounded-lg border border-gray-200 bg-white p-6">'
                                            . ($get('contenido')
                                                ? $get('contenido')
                                                : '<p class="text-gray-400 italic">Escribe contenido en el editor Visual o HTML y regresa aquí para previsualizarlo.</p>')
                                            . '</div>'
                                        ))
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(1),

            // ── Columna lateral (4/12) ──
            Section::make('Publicación')
                ->columnSpan(1)
                ->schema([
                    Toggle::make('publicado')
                        ->label('Publicado')
                        ->helperText('Activa para que sea visible en el blog.')
                        ->default(false)
                        ->live(),

                    DateTimePicker::make('publicado_at')
                        ->label('Fecha de publicación')
                        ->default(now())
                        ->native(false),

                    TextInput::make('autor_nombre')
                        ->label('Autor')
                        ->default('SIMUES')
                        ->maxLength(100),

                    TextInput::make('slug')
                        ->label('Slug (URL)')
                        ->helperText('Se genera automáticamente. Ej: vacunacion-bcg-2026')
                        ->required()
                        ->unique(table: 'posts', column: 'slug', ignoreRecord: true)
                        ->maxLength(255),
                ]),

            Section::make('Imagen y categorización')
                ->columnSpan(1)
                ->schema([
                    FileUpload::make('imagen_portada')
                        ->label('Imagen de portada')
                        ->image()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->imageResizeTargetWidth(1200)
                        ->imageResizeTargetHeight(675)
                        ->directory('blog/portadas')
                        ->disk('public')
                        ->visibility('public')
                        ->helperText('Recomendado: 1200×675 px (16:9).'),

                    Select::make('categoria')
                        ->label('Categoría')
                        ->options(Post::CATEGORIAS)
                        ->default('general')
                        ->required(),

                    TagsInput::make('etiquetas')
                        ->label('Etiquetas')
                        ->helperText('Escribe y pulsa Enter para añadir.')
                        ->placeholder('Añadir etiqueta...'),
                ]),

        ])->columns(3);
    }
}
