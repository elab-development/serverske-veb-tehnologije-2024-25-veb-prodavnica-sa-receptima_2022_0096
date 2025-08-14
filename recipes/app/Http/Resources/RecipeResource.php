<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'category'    => new CategoryResource($this->whenLoaded('category')),
            'creator'        => new UserResource($this->whenLoaded('user')),
            'favorited_by_count' => $this->favoritedBy->count(),
        ];
    }
}
