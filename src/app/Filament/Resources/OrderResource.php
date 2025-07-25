<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use App\Jobs\DecrementStockJob;
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
use Filament\Notifications\Notification;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Pedidos';
    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationGroup = 'Vendas';

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
                    ->required()
                    ->default('in_preparation'),

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
                                    $set('quantity', 1);
                                }
                            })
                            ->live(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->dehydrated()
                            ->rules([
                                function ($get){
                                    return function (string $attribute, $value, $fail) use ($get){
                                      $productId = $get('product_id');
                                      $quantity = (int) $value;
                                      
                                      if(!$productId){
                                        return;
                                      }

                                      $product = Product::with('stock')->find($productId);

                                      if(!$product || !$product->stock){
                                        $fail('Produto não possui registro de estoque');
                                        return;
                                      }

                                      if($quantity > $product->stock->quantity){
                                        $fail("Quantidade indisponível. Estoque atual: {$product->stock->quantity}");
                                      }
                                    };
                                }
                            ])
                            ->live(),

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
                TextColumn::make('created_at')->dateTime()
                    ->label('Data Pedido'),
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
