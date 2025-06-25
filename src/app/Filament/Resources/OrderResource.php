<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Order_item;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Filament\Forms;
use Filament\Forms\Components\HasManyRepeater;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Placeholder;


class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações do Pedido')
                    ->schema([
                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'whatsapp')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->options([
                                'pending' => 'Pendente',
                                'in_preparation' => 'Em Preparação',
                                'finished' => 'Concluído',
                                'canceled' => 'Cancelado'
                            ])
                            ->default('in_preparation')
                            ->required(),

                        Select::make('type')
                            ->options([
                                'counter' => 'Balção',
                                'delivery' => 'Entrega',
                                'withdrawal' => 'Retirada'
                            ])
                            ->default('counter')
                            ->required()
                    ])
                    ->columns(3),


                Textarea::make('observation')
                    ->label('Observação')
                    ->columnSpanFull(),

                Section::make('Items Pedido')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produto')
                                    ->options(Product::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set){
                                        $product = Product::find($state);
                                        if($product){
                                            $set('price_unity', $product->price);
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->label('Quantidade')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $price = $get('price_unity') ?? 0;
                                        $set('subtotal', $state * $price);
                                    }),
                                TextInput::make('price_unity')
                                    ->label('Preço Unitário')
                                    ->disabled()
                                    ->numeric()
                                    ->dehydrated(),
                                
                                TextInput::make('subtotal')
                                    ->label('Subtotal Pedido')
                                    ->disabled()
                                    ->numeric()
                                    ->dehydrated()
                                    ->default(0),
                            ])
                            ->columns(4)
                            ->addActionLabel('adicionar Item')
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $items = $get('items') ?? [];
                                $total = collect($items)->sum(fn($item) =>
                                    ($item['quantity'] ?? 0) * ($item['price_unity'] ?? 0)
                                );

                                $set('total', $total);
                            }),

                TextInput::make('total')
                    ->label('Total do Pedido')
                    ->disabled()
                    ->numeric()
                    ->dehydrated()
                    ->default(0),
                    ]),

                Section::make('Resumo do Pedido')->schema([
                    Placeholder::make('comanda')
                        ->label('Comanda')
                        ->content(function (callable $get) {
                            $items = $get('items') ?? [];
                            if (empty($items)) return 'Nenhum item adicionado.';

                            return collect($items)->map(function ($item, $index) {
                                $produto = \App\Models\Product::find($item['product_id']);
                                $nome = $produto?->name ?? 'Produto removido';
                                $quantidade = $item['quantity'] ?? 0;
                                $preco = number_format($item['price_unity'] ?? 0, 2, ',', '.');
                                $subtotal = number_format($item['subtotal'] ?? 0, 2, ',', '.');

                                return "{$quantidade}x {$nome} ({$preco} cada) → R$ {$subtotal}";
                            })->implode('<br>');
                        })
                        ->columnSpanFull()
                        ->extraAttributes(['class' => 'text-sm text-gray-600']),
                ])
            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.whatsapp')
                    ->label('Cliente'),

                TextColumn::make('status'),

                TextColumn::make('observation'),
                
                TextColumn::make('total')
                    ->money('BRL'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
