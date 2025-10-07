<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Models\Restaurant;
use App\Models\RestaurantMenuItem;
use Illuminate\Http\Request;
use App\Http\Resources\MenuItemResource;

class RestaurantMenuController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'user_type:provider', 'phone.verified'])->except('index');
    }

    public function index(Request $request, Restaurant $restaurant)
    {
        $items = $restaurant->menuItems()->with(['category', 'media'])->latest()->paginate(20);
        return MenuItemResource::collection($items);
    }

    public function store(StoreMenuItemRequest $request, Restaurant $restaurant)
    {
        $this->authorize('update', $restaurant);
        $validatedData = $request->validated();
        $menuItem = $restaurant->menuItems()->create($validatedData);

        if ($request->hasFile('image')) {
            $menuItem->addMediaFromRequest('image')->toMediaCollection('menu_item_images');
        }

        // ✅ THE FIX: Return the response through the resource for consistency
        return (new MenuItemResource($menuItem->fresh('media')))
                ->additional(['success' => true, 'message' => 'Menu item created successfully.'])
                ->response()
                ->setStatusCode(201);
    }

    public function update(StoreMenuItemRequest $request, Restaurant $restaurant, RestaurantMenuItem $menuItem)
    {
        $this->authorize('update', $restaurant);
        $validatedData = $request->validated();
        $menuItem->update($validatedData);

        if ($request->hasFile('image')) {
            $menuItem->clearMediaCollection('menu_item_images');
            $menuItem->addMediaFromRequest('image')->toMediaCollection('menu_item_images');
        }
        
        // ✅ THE FIX: Return the response through the resource for consistency
        return (new MenuItemResource($menuItem->fresh('media')))
                ->additional(['success' => true, 'message' => 'Menu item updated successfully.']);
    }

    public function destroy(Restaurant $restaurant, RestaurantMenuItem $menuItem)
    {
        $this->authorize('update', $restaurant);
        $menuItem->delete();
        return response()->json(['success' => true, 'message' => 'Menu item deleted.']);
    }

}