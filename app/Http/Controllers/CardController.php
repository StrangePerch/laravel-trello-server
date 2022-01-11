<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Column;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

require_once(__DIR__ . "./BoardController.php");

class CardController extends Controller
{
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'text' => 'required|string',
            'column_id' => 'required|int'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong input',
                'validator_errors' => $validator->messages()->toArray()
            ], 500);
        }

        $column = Column::find($request->column_id);
        if (!$column) {
            return response()->json([
                'success' => false,
                'message' => 'No column found'
            ], 500);
        }
        $column->load('board');
        return CheckAccess($column->board->id, 3, function ($board) use ($request) {
            $data = [
                "title" => $request->title,
                "text" => $request->text,
                "column_id" => $request->column_id
            ];

            $card = Card::create($data);

            $card->column()->touch();
            $card->column->board()->touch();

            return response()->json([
                'success' => true,
                'message' => 'Card created successfully',
                'card' => $card
            ], 201);
        });
    }

    public function edit(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong input',
                'validator_errors' => $validator->messages()->toArray()
            ], 500);
        }

        $card = Card::find($id);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Card not found',
                'card' => null
            ], 200);
        }
        $card->load('column');
        $card->column->load('board');
        return CheckAccess($card->column->board->id, 3, function ($board) use ($request, $card) {
            $data = [
                "title" => $request->title,
                'text' => $request->text
            ];

            $card->update($data);

            $card->column()->touch();
            $card->column->board()->touch();

            return response()->json([
                'success' => true,
                'message' => 'Card updated successfully',
                'card' => $card
            ], 200);
        });
    }

    public function move($id, $to): \Illuminate\Http\JsonResponse
    {
        $card = Card::find($id);
        if (!Column::find($to)) {
            return response()->json([
                'success' => false,
                'message' => 'No column found'
            ], 404);
        }
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'No card found'
            ], 404);
        }
        $card->load('column');
        $card->column->load('board');
        return CheckAccess($card->column->board->id, 2, function ($board) use ($card, $to) {
            $card->update(['column_id' => $to]);

            $card->column()->touch();
            $card->column->board()->touch();

            return response()->json([
                'success' => true,
                'message' => 'Card moved successfully'
            ], 200);
        });
    }

    public function delete($id): \Illuminate\Http\JsonResponse
    {
        $card = Card::find($id);
        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'No card found'
            ], 404);
        }
        $card->load('column');
        $card->column->load('board');
        return CheckAccess($card->column->board->id, 2, function ($board) use ($card) {
            $card->column()->touch();
            $card->column->board()->touch();

            $card->delete();
            return response()->json([
                'success' => true,
                'message' => 'Card deleted successfully'
            ], 200);
        });
    }
}
