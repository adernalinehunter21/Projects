//Table with pagination in summary page
 $(document).ready(function() {
      $('.pendingTable').dataTable( {
        "searching": false,
        "bLengthChange": false,
        "pageLength": 5,
        "bInfo": false,
        // "pagingType": "simple",
        

                 } );
  } );


 //call stacked bar chart 

renderStackedBarChart(json_data1,'stackedBarChart');
renderStackedBarChart(json_data2,'stackedBarChartQuiz');
renderStackedBarChart(json_data3,'stackedBarChartExamPrep');
renderStackedBarChart(json_data4,'stackedBarChartReflection');