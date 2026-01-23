<?php

namespace StuMason\Kick\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use StuMason\Kick\Services\LogReader;

class LogController extends Controller
{
    public function __construct(
        protected LogReader $logReader
    ) {}

    /**
     * List all available log files.
     */
    public function index(): JsonResponse
    {
        $files = $this->logReader->listFiles();

        return response()->json([
            'files' => $files,
        ]);
    }

    /**
     * Read entries from a specific log file.
     */
    public function show(Request $request, string $file): JsonResponse
    {
        $lines = (int) $request->query('lines', 100);
        $offset = (int) $request->query('offset', 0);
        $search = $request->query('search');
        $level = $request->query('level');

        try {
            $result = $this->logReader->read(
                $file,
                $lines,
                $offset,
                $search,
                $level
            );

            return response()->json([
                'file' => $file,
                'entries' => $result['entries'],
                'total_lines' => $result['total_lines'],
                'has_more' => $result['has_more'],
                'lines_requested' => $lines,
                'offset' => $offset,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
