<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Log;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('client_id')
                    ->relationship('client', 'whatsapp')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('status')
                    ->options([
                        'pending' => 'Pendente',
                        'in_preparation' => 'Em Preparação',
                        'finished' => 'Finalizado',
                        'canceled' => 'Cancelado'
                    ])
                    ->required(),

                Select::make('type')
                    ->options([
                        'counter' => 'Balção',
                        'delivery' => 'Entrega',
                        'withdrawal' => 'Retirada'
                    ])
                    ->default('counter'),

                Textarea::make('observation')
                    ->columnSpanFull(),

                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Select::make('product_id')
                            ->label('Produto')
                            ->options(Product::pluck('name', 'id'))
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
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->dehydrated(),

                        TextInput::make('price_unity')
                            ->disabled()
                            ->numeric()
                            ->dehydrated()
                    ])
                    ->columns(3)
                    ->addActionLabel('Adicionar Item')
                    ->afterStateUpdated(function (callable $get, callable $set){
                        $items = $get('items') ?? [];
                        $total = collect($items)->sum(fn($item) => ($item['quantity'] ?? 0) * ($item['price_unity'] ?? 0));

                        $set('total', $total);
                    }),

                    TextInput::make('total')
                        ->disabled()
                        ->numeric()
                        ->dehydrated()
                        ->default(0)
            ]);
    }

    public static function saved(Form $form): void{
        Log::info('OrderResource saved() foi chamado!');
        dd('saved chamado');

        $order = $form->getRecord();
        dd($order->status);

        if($order->status === 'finished'){
            Log::info('Pedido finalizado, atualizando estoque...');

            $order->loadMissing('items.product.stock');
            $order->finalizeOrder();
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('client.whatsapp')->label('Cliente'),
                TextColumn::make('status'),
                TextColumn::make('total')->money('BRL'),
                TextColumn::make('created_at')->dateTime(),
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
