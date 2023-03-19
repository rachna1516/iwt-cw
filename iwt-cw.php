<?php
header('Content-Type: application/json; charset=utf-8');

// get the query parameters
$year = $_GET["year"];
$year_op = $_GET["yearOp"];
$tournament = $_GET["tournament"];
$file = $_GET["file"];
$winner = $_GET["winner"];
$runner_up = $_GET["runnerUp"];

//using query parameter to call search function and return error in jason
$results = search($year, $year_op, $tournament, $winner, $runner_up, $file);
echo $results;

/*
 * Results can be searched for according to year, year_op, tournament, winner, runner-up, and file parameters
 * Return JSON
 */
function search($year, $year_op, $tournament, $winner, $runner_up, $file)
{
  //get contents of both json files and decode them.
  $mens_json = file_get_contents('resources/mens-grand-slam-winners.json');
  $womens_json = file_get_contents('resources/womens-grand-slam-winners.json');
  $decoded_mens_json = json_decode($mens_json, true);
  $decoded_womens_json = json_decode($womens_json, true);

  //verify  if file param matches any of the file names or 'any' to use them both.
  //call getSearchResult() with the json, year, year_op, tournament, winner and runner_up params and return the result
  // If it is not showing then throw error for invalid file name.
  switch ($file) {
    case 'mens-grand-slam-winners.json':
      $results = getSearchResult($decoded_mens_json, $year, $year_op, $tournament, $winner, $runner_up);
      return json_encode($results);
    case 'womens-grand-slam-winners.json':
      $results = getSearchResult($decoded_womens_json, $year, $year_op, $tournament, $winner, $runner_up);
      return json_encode($results);
    case 'any':
      $decoded_mixed_json = array_merge($decoded_mens_json, $decoded_womens_json);
      //Sort the mixed json array  in descending order using year
      usort($decoded_mixed_json, function($a, $b) {
        return $a['year'] > $b['year'] ? -1 : 1;
      });
      $results = getSearchResult($decoded_mixed_json, $year, $year_op, $tournament, $winner, $runner_up);
      return json_encode($results);
    default:
      $error_message = new stdClass();
      $error_message->error = "Error! Invalid value for file.";
      return json_encode($error_message);
  }
}

/*
 * Validate the function for the search by year, year condition, tournament, winner and runnerup
 * Return results array or error object.
 */
function getSearchResult($data_json, $search_year, $year_condition, $search_tournament, $search_winner, $search_runnerup)
{
  //Create the results and error objects from scratch to return.
  $results = array();
  $error_message = new stdClass();

  //Use a loop to iterate through the json data and compare it to each parameter matching function.
  //create error object and return when the first error is encountered.
  foreach ($data_json as $item) {
    $year = $item['year'];
    $tournament = $item['tournament'];
    $winner = $item['winner'];
    $runnerup = $item['runner-up'];
    //calls function to verify and compare year parameter
    $check_year = checkYear($year, $search_year, $year_condition);
    if ($check_year === true) {
      //calls function to varify and analyaze tournament parameter.
      $check_tournament = checkTournament($tournament, $search_tournament);
      if ($check_tournament === true) {
        //calls function to verify and compare winner parameter.
        $check_winner = checkWinner($winner, $search_winner);
        if ($check_winner === true) {
          //calls function to varify and compare runnerup parameter.
          $check_runner_up = checkRunnerup($runnerup, $search_runnerup);
          if ($check_runner_up === true) {
            //item matched, hence adding into results to array
            $results[] = $item;
          } elseif ($check_runner_up !== false) {
            //error exists in runner up check, create error object and return
            $error_message->error = $check_runner_up;
            return $error_message;
          }
        } elseif ($check_winner !== false) {
          //error exists in winner check, create error object and return
          $error_message->error = $check_winner;
          return $error_message;
        }
      } elseif ($check_tournament !== false) {
        //error exists in tournament check, create error object and return
        $error_message->error = $check_tournament;
        return $error_message;
      }
    } elseif ($check_year !== false) {
      //error exists in year check, create error object and return
      $error_message->error = $check_year;
      return $error_message;
    }
  }
  //returns the results array - can be of length >= 0
  return $results;
}

/*
 * function to verify and compare year from json data with query parameter year and yearOp
 * Return true if years do match condition or  it is null
 * Return false if years do not match condition
 * Return string with error message in case validations for
 * - non numeric year value,
 * - empty condition with non-empty year,
 */
function checkYear($item_year, $search_year, $year_condition): bool|string
{
  if ($search_year == null) {
    return true;
  }
  if (!is_numeric($search_year)) {
    return "Error! Year should be only numbers.";
  }
  if ($search_year != null && $year_condition == null) {
    return "Error! Search condition cannot be empty with a year.";
  }
  switch ($year_condition) {
    case "=":
      return $item_year == $search_year;
    case ">":
      return $item_year > $search_year;
    case "<":
      return $item_year < $search_year;
    default:
      return "Error! Invalid year condition.";
  }
}

/*
 * function to verify and compare tournament from json data with query parameter tournament
 * Return true if tournament parameter is any or matches the list tournaments listed and the data tournament value.
 * Return false if query param value does not match data tournament value.
 * Return string with error message if the value is not any or defined tournament names.
 */
function checkTournament($tournament, $search_tournament): bool|string
{
  switch ($search_tournament) {
    case 'any':
      return true;
    case 'Australian Open':
    case 'U.S. Open':
    case 'French Open':
    case 'Wimbledon':
      return $search_tournament == $tournament;
    default:
      return 'Error! Invalid tournament name.';
  }
  return false;
}

/*
 * function to verify and compare winner from json data with query parameter winner
 * Return true if json data winner contains query parameter or query parameter is empty.
 * Return false if json data winner does not contain query parameter
 * Return string with error message if name contains numeric values.
 */
function checkWinner($winner, $search_winner): bool|string
{
  if ($search_winner == "") {
    return true;
  }
  if (preg_match('~[0-9]+~', $search_winner)) {
    return "Error! Winner name cannot contain numbers.";
  }
  if (stripos($winner, $search_winner) !== false) {
    return true;
  }
  return false;
}

/*
 * This function is used to validate and compare the runnerup from json data with query parameter runnerup
 * Return true if json data runnerup contains query parameter or query parameter is empty.
 * Return false if json data runner does not contain query parameter
 * Return string with error message if name contains numeric values.
 */
function checkRunnerup($runnerup, $search_runnerup): bool|string
{
  if ($search_runnerup == "") {
    return true;
  }
  if (preg_match('~[0-9]+~', $search_runnerup)) {
    return "Error! Runner-up name cannot contain numbers.";
  }
  if (stripos($runnerup, $search_runnerup) !== false) {
    return true;
  }
  return false;
}

?>
