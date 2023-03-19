$(document).ready(function (load) {

  const targetDiv = document.getElementById("output");
  const btn = document.getElementById("clear-output");
  btn.onclick = function () {

    targetDiv.style.display = "none";
    location.reload();

    
  };
 
  //method to clear output div.
  function clearResult() {
    document.getElementsByClassName("output")[0].style.display = 'none';

    //document.getElementById("elementID").innerHTML = "";
  }
  
  //function would be triggered on search-form submission.
  $("#search-form").submit(function (event) {
    //serialise form data for passing as query parameters. Replacing params with hyphen to remove hyphen for PHP.
    var formData = $("form#search-form").serialize()
    .replace('year-op', 'yearOp')
    .replace('runner-up', 'runnerUp');
    //make request to PHP script with above query params.
    var request = $.getJSON('./iwt-cw.php?', formData);

    //Unhide output div for results/error display
    $('#output').removeClass('hidden');

    //clearing existing errors
    $('#error-message').empty();
    $('#error-message').addClass('hidden');

    //clearing existing results
    $('table#result-table').empty();
    $('table#result-table').addClass('hidden');

    //process the results from the request
    request.done(function (results) {
      //check if result contains error message.
      // If so, unhide the error message element
      // and display the error message from result.

      if (results.error != undefined) {
        $('#error-message').removeClass('hidden');
        $('#error-message').append('<b>' + results.error + '</b>');
      }
      // Else, we check for search results.
      else {
        //if length is 0, display no results error message.
        if (results.length == 0) {
          $('#error-message').removeClass('hidden');
          $('#error-message').append('<b> No results found.</b>');
        }
        //if length not 0, loop through the results and add into the results table.
        else {
          $('table#result-table').removeClass('hidden');

          //add the head row and body to the table.
          $('table#result-table').append(
            '<thead><tr>' +
            '<td><b>' +
            'YEAR' +
            '</b></td>' +
            '<td><b>' +
            'TOURNAMENT' +
            '</b></td>' +
            '<td><b>' +
            'WINNER' +
            '</b></td>' +
            '<td><b>' +
            'RUNNER-UP' +
            '</b></td>' +
            '</tr></thead>' +
            '<tbody></tbody>');

          //loop through results and create rows in the table body.
          $.each(results, function (index, resultItem) {
            $('table#result-table tbody').append(
              '<tr>' +
              '<td>' +
              resultItem["year"] +
              '</td>' +
              '<td>' +
              resultItem["tournament"] +
              '</td>' +
              '<td>' +
              resultItem["winner"] +
              '</td>' +
              '<td>' +
              resultItem["runner-up"] +
              '</td>' +
              '</tr>');
          });
        }
      }
    });
    event.preventDefault();
  });
  
});