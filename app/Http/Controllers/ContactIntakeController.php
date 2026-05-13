<?php

namespace App\Http\Controllers;

use App\Mail\NewContactIntake;
use App\Services\ContactIntake\ParseVCard;
use App\Services\ContactIntake\ProcessIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactIntakeController extends Controller
{
    public function show()
    {
        return view('intake.form');
    }

    public function thanks()
    {
        return view('intake.thanks');
    }

    public function submit(Request $request, ProcessIntake $processor)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:50',
            'email'   => 'nullable|email|max:255',
            'company' => 'nullable|string|max:255',
            'note'    => 'nullable|string|max:2000',
            'vcf'     => 'nullable|file|max:512',
        ]);

        $data = $request->only(['name', 'phone', 'email', 'company', 'note']);

        if ($request->hasFile('vcf')) {
            $data['raw_vcf'] = $request->file('vcf')->get();
        }

        $submission = $processor->store($data, $request->ip());

        if ($submission->status === 'pending') {
            Mail::to('arta.seyedian@gmail.com')->send(new NewContactIntake($submission));
        }

        return redirect()->route('intake.thanks')->with('submitted_name', $data['name']);
    }

    public function parseVCard(Request $request)
    {
        if (! $request->hasFile('vcf') || ! $request->file('vcf')->isValid()) {
            return response()->json(['error' => 'No valid file received.'], 422);
        }

        if ($request->file('vcf')->getSize() > 512 * 1024) {
            return response()->json(['error' => 'File too large.'], 422);
        }

        try {
            $parsed = app(ParseVCard::class)->parse($request->file('vcf')->get());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not read contact card.'], 422);
        }

        return response()->json($parsed);
    }
}
