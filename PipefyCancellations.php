<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

class PipefyCancellations extends Controller
{
  public function pipefy_cancellation(Request $request)
  {
    $json = json_decode($request->getContent());
    $card_id = $json->data->card->id;
    $client = new Client();
    
    // Pipefy query to obtain all cards with field details and phases history
    $query = (object)[
      "query" => "{
        card(id: " . $card_id . ") {
          createdAt
          title
          current_phase {
            id
            name
          }
          pipe{
            id
          }        
          fields {
            phase_field {
              id
            }
            name
            value
          }
   				phases_history {
            phase {
            	id
            	name
          	}
            firstTimeIn
            duration
          }
          url
        }
      }"
    ];

    // Obtaining the query results
    $result = $client->post('https://api.pipefy.com/graphql', [
      'headers' => [
        'content-type' => 'application/json',
        'Accept' => 'x',
        'Accept-Encoding' => 'x',
        'authorization' => 'x'
      ],
      'json' => $query
    ]);
    $out = $result->getBody()->getContents();
    $json = json_decode($out);
    $card = $json->data->card;

    // Checking if there is valid response
    if ($json != null && $json->data != null && $json->data->card != null) 
    {
      // Obtaining the more external and non-array fields
      $card = $json->data->card;
      $card_phase = trim($card->current_phase->name);
      // Initializing variables with easier fields and NULLs to prevent unset value errors
      $dataset = [
        "card_id" => $card_id,
        "current_phase" => $card_phase,
        "name" => NULL,
        "document" => NULL,
        "email" => NULL,
        "cel" => NULL,
        "date" => NULL,
        "scholarship" => NULL,
        "course" => NULL,
        "reason" => NULL,
        "fee" => NULL,
        "log_phases" => NULL,
        "1st_phase_date" => $card->createdAt,
        "2nd_phase_date" => NULL,
        "3rd_phase_date" => NULL
      ];
      
      // Looping through the phases history to build a log of all phases the card entered, and record when it happened
      if($card->phases_history != null) {
        $phases = $card->phases_history;
        $log_phases = "";
        for ($x = 0; $x < sizeof($phases); $x++) {
          if($phases[$x]->phase->id == "x") {
            $log_phases .= "1,";
          }
          if($phases[$x]->phase->id == "y") {
            $dataset["2nd_phase_date"] = $phases[$x]->firstTimeIn;
            $log_phases .= "2,";
          }
          if($phases[$x]->phase->id == "z") {
            $dataset["3rd_phase_date"] = $phases[$x]->firstTimeIn;
            $log_phases .= "3,";
          }
        }
        $dataset["log_phases"] = rtrim($log_phases, ", ");
      }

      // These are pipefy's field names, obtained via their graph api
      $matrix = ["name","document","email","cel","date","scholarship","course","reason","fee"];

      // Looping through all fields in the data and saving them to their according field in our final dataset
      if ($card->fields != null) {
        $card_fields = $card->fields;
        for ($x = 0; $x < sizeof($card_fields); $x++) {
          if ($card_fields[$x]->phase_field->id == $matrix[0]) {
            $dataset["name"] = $card_fields[$x]->value;
          }          
          if ($card_fields[$x]->phase_field->id == $matrix[1]) {
            $dataset["document"] = $card_fields[$x]->value;
          }
          if ($card_fields[$x]->phase_field->id == $matrix[2]) {
            $dataset["email"] = $card_fields[$x]->value;
          }
          if ($card_fields[$x]->phase_field->id == $matrix[3]) {
            $dataset["cel"] = $card_fields[$x]->value;
          }
          if ($card_fields[$x]->phase_field->id == $matrix[4]) {
            $dataset["date"] = date_format(date_create_from_format('d/m/Y',$card_fields[$x]->value),'Y-m-d');
          }
          if ($card_fields[$x]->phase_field->id == $matrix[5]) {
            $dataset["scholarship"] = $card_fields[$x]->value;
          }
          if ($card_fields[$x]->phase_field->id == $matrix[6]) {
            $dataset["course"] = $card_fields[$x]->value;
          }
          if ($card_fields[$x]->phase_field->id == $matrix[7]) {
            $dataset["reason"] = $card_fields[$x]->value;
          }
          if ($card_fields[$x]->phase_field->id == $matrix[13]) {
            $dataset["fee"] = str_replace(',', "",$card_fields[$x]->value);
          }
        }
      }
      // // Debugging for the final dataset
      // dd($dataset);

      // Checking if there is already an existing row in the database with this pipefy's card_id
      $card_id_check = DB::table('cancellation')
        ->where('card_id',$card_id)
        ->count();

      // Formatting the final dataset for insertion
      $json = json_encode($dataset);

      // If there is already an existing row, update it, otherwise insert a new row
      if($card_id_check > 0)
      {
        DB::table('cancellation')
          ->where('card_id',$card_id)
          ->update($dataset);
          return $json;
      }
      else
      {
        DB::table('cancellation')
          ->insert($dataset);
          return $json;
      }
    }  
  }
}
