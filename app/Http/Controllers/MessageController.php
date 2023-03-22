<?php

namespace App\Http\Controllers;

use App\Events\Webhook;
use App\Jobs\SendMessage;
use App\Libraries\Whatsapp;
use App\Models\Message;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PhpParser\Node\Expr;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $messages = DB::table('messages', 'm')
                ->whereRaw('m.id IN (SELECT MAX(id) FROM messages m2 GROUP BY wa_id)')
                ->orderByDesc('m.id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $messages,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success'  => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            $request->validate([
                'wa_id' => ['required', 'max:20'],
                'body' => ['required', 'string'],
            ]);

            $input = $request->all();
            $wp = new Whatsapp();
            $response = $wp->sendText($input['wa_id'], $input['body']);

            $message = new Message();
            $message->wa_id = $input['wa_id'];
            $message->wam_id = $response["messages"][0]["id"];
            $message->type = 'text';
            $message->outgoing = true;
            $message->body = $input['body'];
            $message->status = 'sent';
            $message->caption = '';
            $message->data = '';
            $message->save();

            return response()->json([
                'success' => true,
                'data' => $message,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success'  => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function show($waId, Request $request)
    {
        try {
            $messages = DB::table('messages', 'm')
                ->where('wa_id', $waId)
                ->orderBy('created_at')
                ->get();

            foreach ($messages as $key => $message) {
                if ($message->type == 'template') {
                    $message->data = unserialize($message->data);
                }
                $messages[$key] = $message;
            }

            return response()->json([
                'success' => true,
                'data' => $messages,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success'  => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Message $message)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function destroy(Message $message)
    {
        //
    }

    public function sendMessages()
    {
        try {
            // $token = env('WHATSAPP_API_TOKEN');
            // $phoneId = env('WHATSAPPI_API_PHONE_ID');
            // $version = 'v15.0';
            // $buttons = [
            //     "type" => "button",
            //     "body" => [
            //         "text" => 'Hola, soy el bot Juanes!',
            //     ],
            //     'action' => [
            //         'buttons' => [
            //             [
            //                 'type' => 'reply',
            //                 'reply' => [
            //                     'id' => 'button1_id',
            //                     'title' => 'Opcion 1',
            //                 ]
            //             ],
            //             [
            //                 'type' => 'reply',
            //                 'reply' => [
            //                     'id' => 'button2_id',
            //                     'title' => 'Opcion 2',
            //                 ]
            //             ]
            //         ],
            //     ],
            // ];
            // $list = [
            //     'type' => 'list',
            //     "body" => [
            //         "text" => 'Hola, soy el bot Juanes!',
            //     ],
            //     'action' => [
            //         'button' => 'Opciones',
            //         'sections' => [
            //             [
            //                 'title' => 'Seccion 1',
            //                 'rows' => [
            //                     [
            //                         'id' => 'mi_id1',
            //                         'title' => 'Mi titulo',
            //                         'description' => 'My description'
            //                     ],
            //                     [
            //                         'id' => 'mi_id2',
            //                         'title' => 'Mi titulo 2',
            //                         'description' => 'My description 2'
            //                     ],
            //                 ],
            //             ]
            //         ],
            //     ]
            // ];
            // $payload = [
            //     'messaging_product' => 'whatsapp',
            //     'to' => '14842918777',
            //     'type' => 'interactive',
            //     'interactive' => $list,
            // ];

            // $message = Http::withToken($token)->post('https://graph.facebook.com/' . $version . '/' . $phoneId . '/messages', $payload)->throw()->json();

            $buttons = ['Formulario de Registro', 'Horarios', 'Ubicación'];
            $wp = new Whatsapp();
            $message = $wp->sendButton(
                '14842918777',
                'Hola soy Juanes Bot y estoy aquí para ayudarte. Selecciona una de estas opciones.',
                $buttons
            );

            return response()->json([
                'success' => true,
                'data' => $message,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success'  => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyWebhook(Request $request)
    {
        try {
            $verifyToken = env('WHATSAPP_VERIFY_TOKEN');
            $query = $request->query();

            $mode = $query['hub_mode'];
            $token = $query['hub_verify_token'];
            $challenge = $query['hub_challenge'];

            if ($mode && $token) {
                if ($mode === 'subscribe' && $token == $verifyToken) {
                    return response($challenge, 200)->header('Content-Type', 'text/plain');
                }
            }

            throw new Exception('Invalid request');
        } catch (Exception $e) {
            return response()->json([
                'success'  => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function processWebhook(Request $request)
    {
        try {
            $bodyContent = json_decode($request->getContent(), true);
            $body = '';

            // Determine what happened...
            $value = $bodyContent['entry'][0]['changes'][0]['value'];

            if (!empty($value['statuses'])) {
                $status = $value['statuses'][0]['status']; // sent, delivered, read, failed
                $wam = Message::where('wam_id', $value['statuses'][0]['id'])->first();

                if (!empty($wam->id)) {
                    $wam->status = $status;
                    $wam->save();
                    Webhook::dispatch($wam, true);
                }
            } else if (!empty($value['messages'])) { // Message
                $exists = Message::where('wam_id', $value['messages'][0]['id'])->first();

                if (empty($exists->id)) {
                    $mediaSupported = ['audio', 'document', 'image', 'video', 'sticker'];

                    if ($value['messages'][0]['type'] == 'text') {
                        $this->processChatbotMessages($value['messages'][0]['text']['body'], $value['messages'][0]['from']);

                        $message = $this->_saveMessage(
                            $value['messages'][0]['text']['body'],
                            'text',
                            $value['messages'][0]['from'],
                            $value['messages'][0]['id'],
                            $value['messages'][0]['timestamp']
                        );

                        Webhook::dispatch($message, false);
                    } else if ($value['messages'][0]['type'] == 'interactive') {
                        $message = $this->_saveMessage(
                            $value["messages"][0]["interactive"]["button_reply"]["title"],
                            'text',
                            $value['messages'][0]['from'],
                            $value['messages'][0]['id'],
                            $value['messages'][0]['timestamp']
                        );

                        Webhook::dispatch($message, false);
                    } else if (in_array($value['messages'][0]['type'], $mediaSupported)) {
                        $mediaType = $value['messages'][0]['type'];
                        $mediaId = $value['messages'][0][$mediaType]['id'];
                        $wp = new Whatsapp();
                        $file = $wp->downloadMedia($mediaId);

                        $caption = null;
                        if (!empty($value['messages'][0][$mediaType]['caption'])) {
                            $caption = $value['messages'][0][$mediaType]['caption'];
                        }

                        if (!is_null($file)) {
                            $message = $this->_saveMessage(
                                'http://localhost:8000/storage/' . $file,
                                $mediaType,
                                $value['messages'][0]['from'],
                                $value['messages'][0]['id'],
                                $value['messages'][0]['timestamp'],
                                $caption
                            );
                        }
                    } else {
                        $type = $value['messages'][0]['type'];
                        if (!empty($value['messages'][0][$type])) {
                            $message = $this->_saveMessage(
                                "($type): \n _" . serialize($value['messages'][0][$type]) . "_",
                                'other',
                                $value['messages'][0]['from'],
                                $value['messages'][0]['id'],
                                $value['messages'][0]['timestamp']
                            );
                        }
                        Webhook::dispatch($message, false);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $body,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success'  => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function processChatbotMessages($text, $waId)
    {
        $text = strtolower($text);
        $wp = new Whatsapp();

        // $message = Message::where('outgoing', false)->where('wa_id', $waId)->orderByDesc('id')->first();

        // if (!empty($message) && strtolower($message->body) == 'hola') {
        if ($text == 'menu') {
            $buttons = ['Formulario', 'Cafes', 'Ubicación'];

            $wp->sendButton(
                $waId,
                'Hola soy Juanes Bot y estoy aquí para ayudarte. Selecciona una de estas opciones.',
                $buttons
            );
        } else if ($text == 'cafes') {
            $response = Http::get('https://api.sampleapis.com/coffee/hot')->throw()->json();

            $body = '';
            foreach ($response as $item) {
                $body .= $item['title'] . ', ';
            }

            $wp->sendText($waId, $body);
        } else {
            $wp->sendText($waId, 'No entiendo tu respuesta.');
        }
        // } else {
        //     $wp->sendText($waId, 'No seas descortes y por favor saludame.');
        // }
    }

    public function loadMessageTemplates()
    {
        try {
            $wp = new Whatsapp();
            $templates = $wp->loadTemplates();

            return response()->json([
                'success' => true,
                'data' => $templates['data'],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success'  => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendMessageTemplate(Request $request)
    {
        try {
            $input = $request->all();

            $wp = new Whatsapp();
            $templateName = $input['template_name'];
            $templateLang = $input['template_language'];
            $template = $wp->loadTemplateByName($templateName, $templateLang);

            if (!$template) {
                throw new Exception("Invalid template.");
            }

            $templateBody = '';
            foreach ($template['components'] as $component) {
                if ($component['type'] == 'BODY') {
                    $templateBody = $component['text'];
                }
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'type' => 'template',
                "template" => [
                    "name" => $input['template_name'],
                    "language" => [
                        "code" => $input['template_language']
                    ]
                ]
            ];

            $messageData = [];
            if (!empty($input['header_type']) && !empty($input['header_url'])) {
                $type = strtolower($input['header_type']);
                $payload['template']['components'][] = [
                    'type' => 'header',
                    'parameters' => [[
                        'type' => $type,
                        $type => [
                            'link' => $input['header_url'],
                        ]
                    ]],
                ];
                $messageData = [
                    'header_type' => $input['header_type'],
                    'header_url' => $input['header_url'],
                ];
            }

            $body = $templateBody;
            if (!empty($input['body_placeholders'])) {
                $bodyParams = [];
                foreach ($input['body_placeholders'] as $key => $placeholder) {
                    $bodyParams[] = ['type' => 'text', 'text' => $placeholder];
                    $body = str_replace('{{' . ($key + 1) . '}}', $placeholder, $body);
                }
                $payload['template']['components'][] = [
                    'type' => 'body',
                    'parameters' => $bodyParams,
                ];
            }

            $recipients = explode("\n", $input['recipients']);

            foreach ($recipients as $recipient) {
                $phone = (int) filter_var($recipient, FILTER_SANITIZE_NUMBER_INT);
                $payload['to'] = $phone;

                SendMessage::dispatch($payload, $body, $messageData);
            }

            return response()->json([
                'success' => true,
                'data' => count($recipients) . ' messages were enqueued.',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success'  => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function _saveMessage($message, $messageType, $waId, $wamId, $timestamp = null, $caption = null, $data = '')
    {
        $wam = new Message();
        $wam->body = $message;
        $wam->outgoing = false;
        $wam->type = $messageType;
        $wam->wa_id = $waId;
        $wam->wam_id = $wamId;
        $wam->status = 'sent';
        $wam->caption = $caption;
        $wam->data = $data;

        if (!is_null($timestamp)) {
            $wam->created_at = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
            $wam->updated_at = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
        }
        $wam->save();

        return $wam;
    }
}
