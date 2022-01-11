<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

function CheckAccess(int $boardId, int $requiredAccess, callable $onSuccess): JsonResponse
{
    $user_id = Auth::id();
    $board = Board::find($boardId);
    if (!$board) {
        return response()->json([
            'success' => false,
            'message' => 'Board not found',
        ], 404);
    }

    $board->load('users');
    $user = $board->users->find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'You are not a member of that board'
        ], 401);
    }

    $access_level = $user->pivot->access_level;
    if ($access_level < $requiredAccess) {
        return response()->json([
            'success' => false,
            'message' => 'Not enough access'
        ], 401);
    }
    $board->load('users');
    return $onSuccess($board);
}

class BoardController extends Controller
{
    public function store(Request $request): JsonResponse
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
        $owner_id = Auth::id();
        if (!$owner_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $data = [
            "title" => $request->title
        ];

        $board = Board::create($data);
        $board->users()->attach($owner_id, ["access_level" => 5]);

        foreach ($board->columns as $column) {
            $column->load('cards');
        }
        return response()->json([
            'success' => true,
            'message' => 'Board created successfully',
            'board' => $board,
        ], 201);
    }

    public function get(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $user->load('boards');
        $boards = $user->boards;

        if (!$boards) {
            return response()->json([
                'success' => false,
                'message' => 'No boards found',
                'boards' => null
            ]);
        }

        foreach ($boards as $board) {
            foreach ($board->columns as $column) {
                $column->load('cards');
            }
        }
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'boards' => $boards
        ]);
    }

    public function updated(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $user->load('boards');
        $boards = $user->boards;

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'updated_at' => $boards->max('updated_at')
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        return CheckAccess($id, 5, function ($board) {
            $board->delete();
            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully'
            ], 200);
        });
    }

    public function edit(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong input',
                'validator_errors' => $validator->messages()->toArray()
            ], 500);
        }
        return CheckAccess($id, 4, function ($board) use ($request) {
            $data = [
                "title" => $request->title,
            ];
            $board->update($data);
            return response()->json([
                'success' => true,
                'board' => $board
            ], 200);
        });
    }

    public function addUser(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'access_level' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong input',
                'validator_errors' => $validator->messages()->toArray()
            ], 500);
        }
        return CheckAccess($id, 5, function ($board) use ($request) {
            $user = User::where('email', $request['username'])->orWhere('name', $request['username'])->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            if ($board->users->contains('id', $user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already attached'
                ], 404);
            }
            $userResult = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'access_level' => (int)$request->access_level];
            $board->users()->attach($user->id, ['access_level' => $request->access_level]);
            return response()->json([
                'success' => true,
                'message' => 'User attached successfully',
                'user' => $userResult
            ], 201);
        });
    }

    public function updateUser(Request $request, int $id, int $user_id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'access_level' => 'required|int'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Wrong input',
                'validator_errors' => $validator->messages()->toArray()
            ], 500);
        }
        return CheckAccess($id, 5, function ($board) use ($request, $user_id) {
            $board->users()->detach($user_id);
            $board->users()->attach($user_id, ['access_level' => $request->access_level]);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully'
            ], 201);
        });
    }

    public function deleteUser(int $id, int $user_id): JsonResponse
    {
        return CheckAccess($id, 5, function ($board) use ($user_id) {
            $board->users()->detach($user_id);

            return response()->json([
                'success' => true,
                'message' => 'User detached successfully'
            ], 201);
        });
    }

    public function getUsers(int $id): JsonResponse
    {
        return CheckAccess($id, 1, function ($board) {
            $users = $board->users->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'email' => $item->email,
                    'access_level' => $item->pivot->access_level];
            })->all();
            return response()->json([
                'success' => true,
                'message' => 'Success',
                'users' => $users,
            ], 201);
        });
    }

    public function getAccessLevel($id): JsonResponse
    {
        $user_id = Auth::id();
        $board = Board::find($id);
        if (!$board) {
            return response()->json([
                'success' => false,
                'message' => 'Board not found',
            ], 404);
        }
        $board->load('users');
        $user = $board->users->find($user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of that board'
            ], 401);
        }
        $access_level = $user->pivot->access_level;
        return response()->json([
            'success' => true,
            'message' => 'Success',
            'access_level' => $access_level,
        ], 201);
    }
}
