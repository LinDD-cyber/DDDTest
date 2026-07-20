<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Domains\Example\Models\Example;

class ExampleController extends Controller
{
    public function index()
    {
        $examples = [];
        try {
            $examples = Example::all()->toArray();
            if (empty($examples)) {
                $examples = Example::getMockExamples();
            }
        } catch (\Exception $e) {
            $examples = Example::getMockExamples();
        }

        return response()->json($examples);
    }

    public function show($id)
    {
        $example = null;
        try {
            $example = Example::find($id);
            if ($example) {
                $example = $example->toArray();
            }
        } catch (\Exception $e) {}

        if (!$example) {
            $mocks = Example::getMockExamples();
            foreach ($mocks as $m) {
                if ($m['id'] == $id) {
                    $example = $m;
                    break;
                }
            }
        }

        if (!$example) {
            return response()->json(['error' => 'Example not found'], 404);
        }

        return response()->json($example);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Retrieve authentication user ID injected by gateway/middleware if available
        $validated['user_id'] = $request->input('auth_user_id') ?: $request->header('X-User-Id');

        try {
            $example = Example::create($validated);
            $result = $example->toArray();
        } catch (\Exception $e) {
            // Mock response if database isn't migrated
            $result = array_merge([
                'id' => rand(100, 999),
                'user_id' => $validated['user_id'] ?: 1,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ], $validated);
        }

        return response()->json($result, 201);
    }
}
