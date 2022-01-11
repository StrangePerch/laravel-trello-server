<?php

namespace App\Http\Controllers;

use App\Models\Column;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Type\Integer;

require_once(__DIR__ . "./BoardController.php");

class ColumnController extends Controller
{
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'board_id' => 'required|int'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong input',
                'validator_errors' => $validator->messages()->toArray()
            ], 500);
        }
        return CheckAccess($request->board_id, 4, function () use ($request) {
            $data = [
                "title" => $request->title,
                "board_id" => $request->board_id
            ];

            $column = Column::create($data);

            $column->board()->touch();

            return response()->json([
                'success' => true,
                'message' => 'Column created successfully',
                'column' => $column
            ], 201);
        });
    }

    public function edit(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong input',
                'validator_errors' => $validator->messages()->toArray()
            ], 500);
        }
        $column = Column::find($id);
        if (!$column) {
            return response()->json([
                'success' => false,
                'message' => 'Column not found',
                'column' => false
            ], 404);
        }
        $column->load('board');
        return CheckAccess($column->board->id, 3, function () use ($request, $column) {
            $data = [
                "title" => $request->title
            ];
            $column->update($data);
            $column->load('cards');

            $column->board()->touch();


            return response()->json([
                'success' => true,
                'message' => 'Column updated successfully',
                'column' => $column
            ], 200);
        });
    }


    public function delete($id): \Illuminate\Http\JsonResponse
    {
        $column = Column::find($id);
        if (!$column) {
            return response()->json([
                'success' => false,
                'message' => ['No column found']
            ], 404);
        }
        $column->load('board');
        return CheckAccess($column->board->id, 4, function () use ($column) {
            $column->delete();

            $column->board()->touch();

            return response()->json([
                'success' => true,
                'message' => 'Column deleted successfully'
            ], 200);
        });
    }
}
