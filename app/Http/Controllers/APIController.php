<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Twilio\TwiML\MessagingResponse;

class APIController extends Controller
{
    public function __construct()
    {
        //
    }

    public function showFixture(Request $request,$fixtureId){
        $table = "";
        $fixName = "";
        $fixVenue = "";
        $fixDate = "";

        $fxt = DB::table('fixture_minute')
            ->selectRaw('fixture_date,fixture_venue,fixture_opp,player_name,minute,fixture_date')
            ->join("fixtures", "fixture_minute.fixture_id", "=", "fixtures.id")
            ->join("player", "fixture_minute.player_id", "=", "player.id")
            ->where('fixture_minute.fixture_id',$fixtureId)
            ->orderBy('minute')
            ->get();

        foreach($fxt as $row){
            $fixName = $row->fixture_opp;
            $fixDate = $row->fixture_date;
            $fixVenue = $row->fixture_venue;

            $table .= "<tr><td style='text-align:center'>".$row->minute."</td><td>".$row->player_name."</td></tr>";
        }

        
        
        $html = "
            <!DOCTYPE html>
            <html lang='en'>
                <head>
                    <title>Fixture - $fixName ($fixVenue)</title>
                    <link rel='stylesheet' type='text/css' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.1/css/bootstrap.min.css' />
                    <link rel='stylesheet' type='text/css' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.1/css/bootstrap-grid.min.css' />
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                </head>
                <body>
                    <br />
                    <div class='container'>
                        <h1>Minutes for $fixName ($fixVenue)</h1>
                        <br/>
                        <table class='table'>
                            <tr>
                                <th style='text-align:center'>Minute</th>
                                <th style='text-align:left'>Player</th>
                            </tr>
                            $table
                        </table>
                    </div>
                </body>
            </html>
        ";

        echo $html;
    }

    public function processInboundMinute(Request $request){
        $smsNumber = "";
        $minute = 0;
        $message = "Sorry, I don't understand your message.";

        if ($request->input("From") != ""){
            $smsNumber = $request->input("From");
            $smsNumber = str_replace("+","",$smsNumber);
            $smsNumber = trim($smsNumber);
        }
        if ($request->input("Body") != ""){
            $body = $request->input("Body");
            if (strtolower($body) != "show"){
                $minute = (int) filter_var($body, FILTER_SANITIZE_NUMBER_INT);  
            }
        }

        if (strtolower($body) == "show"){
            $fxt = DB::table('fixtures')
                ->whereRaw("fixture_date >= '" . date('Y-m-d H:i:s') . "'")
                ->orderBy('fixture_date', 'asc')
                ->first();

            if ($fxt != null){
                $fixtureId = $fxt->id;
                $message = "Please click the link to see the minutes for the next fixture - https://first-goal-scorer.nw.r.appspot.com/fixture/" . $fixtureId;
            }
        }
        else{
            if (($smsNumber != "") && ($minute > 0 && $minute <= 91)){
                // Get the player
                $ply = DB::table('player')
                    ->where('player_number', $smsNumber)
                    ->first();

                
                // Do we have a player?
                if ($ply != null){
                    // Get the next fixture
                    $fxt = DB::table('fixtures')
                        ->whereRaw("fixture_date >= '" . date('Y-m-d H:i:s') . "'")
                        ->orderBy('fixture_date', 'asc')
                        ->first();

                    if ($fxt != null){
                        $playerId = $ply->id;
                        $fixtureId = $fxt->id;

                        $oppt = $fxt->fixture_opp;
                        $fixv = $fxt->fixture_venue;

                        // Check minute is available
                        $min = DB::table("fixture_minute")
                            ->join("fixtures", "fixture_minute.fixture_id", "=", "fixtures.id")
                            ->where("fixture_id", $fixtureId)
                            ->where("minute",$minute)
                            ->first();

                        if ($min == null){
                            // Remove the minute
                            DB::table("fixture_minute")
                                ->where("fixture_id", $fixtureId)
                                ->where("player_id",$playerId)
                                ->delete();
                            
                            DB::table("fixture_minute")
                                ->insert([
                                    "fixture_id" => $fixtureId,
                                    "minute" => $minute,
                                    "player_id" => $playerId
                                ]);
                            
                            $message = "Your minute has now been assigned as " . $minute . " minute for " . $oppt . " (" . $fixv . ")"; 
                        }
                        else{
                            $message = "Sorry, the " . $minute . " minute is already taken for " . $min->fixture_opp . " (" . $min->fixture_venue . ")";
                        }
                    }
                    else{
                        $message = "Sorry, I couldn't find the next fixture";
                    }
                }
                else{
                    $message = "Sorry, I couldn't find someone with the number " . $smsNumber;
                }
            }
            else{
                $message = "Sorry, I don't think the minute you've entered is valid";
            }
        }

        $response = new MessagingResponse();
        $response->message($message);
        print $response;
        
    }

    public function test(Request $request){
        echo "Testy McTestFace";
        // dd($_SERVER);
    }

    public function testDBConnection(){
        echo ">> TEST DB Connection";

        // echo base_path();

        $results = DB::select("SELECT * FROM player");

        echo ">> CONNECTED";
    }

    public function logEntry(Request $request){
       
        $rq = $request->all();
        $log = "";

        foreach($rq as $key => $value){
            if (!is_array($value)){
                $log .= $key . ": " . $value . ";\n";
            }
        }

        DB::table("log")
            ->insert([
                "log_entry" => $log,
                "log_date" => date("Y-m-d H:i:s")
            ]);
        
    }

    //
}