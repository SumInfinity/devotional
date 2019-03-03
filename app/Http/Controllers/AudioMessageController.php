<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;

use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;

use App\AudioMessage;
use App\Http\Resources\AudioCollection;

class AudioMessageController extends Controller
{
    public $shouldTransrcibe = false;

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|required|max:255',
            'src_url' => 'string|required|max:255',
            'full_text' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'author_id' => 'nullable|integer|exists:users,id',
            'church_id' => 'nullable|integer|exists:churches,id',
            'profile_media_id' => 'nullable|integer|exists:profile_media,id',
            'size' => 'nullable|integer|max:255',
            'length' => 'nullable|integer',
            'language' => 'nullable|string',
            'recorded_at' =>  'nullable|string|max:255',
            'address_id' => 'nullable|integer|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $data = collect($request->all())->toArray();
        $data['uploader_id'] = Auth::user()->id;
        $result = AudioMessage::create($data);
        //obtain length,size and details of audio
        $result = $this->getTrackDetails($result);
        $result= $this->getTrackFullText($result);


        if ($result) {
            return response()->json(['data'=>true], 201);
        } else {
            return response()->json(['data'=>false,'errors'=>'unknown error occured'], 400);
        }
    }

    public function getTrackDetails(AudioMessage $audio): AudioMessage
    {
        //here use libmp3 to get track details and update it
        //then return the object back
        return $audio;
    }

    public function getTrackFullText(AudioMessage $audio): AudioMessage
    {
        if ($this->shouldTransrcibe) {
            //connect to google
            $content = file_get_contents($audio->src_url);
            $googleAudio = (new RecognitionAudio())->setContent($content);

            # The audio file's encoding, sample rate and language
            $config = new RecognitionConfig([
            'encoding' => AudioEncoding::LINEAR16,
            'sample_rate_hertz' => 32000,
            'language_code' => 'en-US'
        ]);

            # Instantiates a client
            $client = new SpeechClient();
            # Detects speech in the audio file
            $response = $client->recognize($config, $googleAudio);
            # Print most likely transcription
            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                $mostLikely = $alternatives[0];
                $transcript = $mostLikely->getTranscript();
                printf('Transcript: %s' . PHP_EOL, $transcript);
            }
            $client->close();
            //change audio to text
        //save it then return object
        }
        return $audio;
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'integer|required|exists:audio_messages,id',
            'name' => 'string|required|max:255',
            'src_url' => 'string|required|max:255',
            'full_text' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'author_id' => 'nullable|integer|exists:users,id',
            'church_id' => 'nullable|integer|exists:churches,id',
            'profile_media_id' => 'nullable|integer|exists:profile_media,id',
            'size' => 'nullable|integer|max:255',
            'length' => 'nullable|integer',
            'language' => 'nullable|string',
            'recorded_at' =>  'nullable|string|max:255',
            'address_id' => 'nullable|integer|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $data = collect($request->all())->toArray();
        $data['user_id'] = Auth::user()->id;
        $result = $this->getTrackDetails($result);
        $result= $this->getTrackFullText($result);
        $id = $request->route('id');
        $result = AudioMessage::find($id);
        //update result
        $result = $result->update($data);


        if ($result) {
            return response()->json(['data'=>true], 201);
        } else {
            return response()->json(['data'=>false,'errors'=>'unknown error occured'], 400);
        }
    }

    public function get(Request $request)
    {
        $id = (int)$request->route('id');
        if ($audio = AudioMessage::find($id)) {
            return response()->json([
                'data' => $audio
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }

    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'nullable|string|min:3'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $query = $request['q'];
        $audia = AudioMessage::where('audio_messages.id', '>', '0')->with('churches')->with('recorder');
        if ($query) {
            $audia = $audia->search($query);
        }
        //here insert search parameters and stuff
        $length = (int)(empty($request['perPage']) ? 15 : $request['perPage']);
        $audia = $audia->paginate($length);
        $data = new AudioCollection($audia);
        return response()->json($data);
    }


    public function delete(Request $request)
    {
        $id = (int)$request->route('id');
        if ($audio = AudioMessage::find($id)) {
            $audio->delete();
            return response()->json([
                'data' => true
            ], 200);
        } else {
            return response()->json([
                'data' => false
            ], 404);
        }
    }
}
