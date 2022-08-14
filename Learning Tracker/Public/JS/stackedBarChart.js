function renderStackedBarChart(json_data,chartId){


var chart = c3.generate({

    
    bindto: '#'+chartId,
    // bindto: '#stackedBarChart',
    data: {
        json: json_data,
        keys: {
            value: ['points_earned','points_available']
        },
        type:'bar',
       
        groups:[
            ['points_earned','points_available']
        ],
    },

     color: {
                pattern: ["#ade2eb", "#02435c"],
                
            },
    axis: {
        y: {
            label: {
                text: 'Points',
                position: 'outer-middle'
            },
            tick: {
                format: d3.format("") // ADD
            }
        },
        // chart.axis.range({min: {x: 1}, max: {x: 100}});
        x: {
            show: true,
            label: {
                text: 'Session',
                position: 'outer-center',
                fontSize: '5px'
            },
            tick: {
                format: function (x) {

                    return json_data[x]['session_index'];
                }
            }
        }
    },
    tooltip: {
    grouped: false,
     // contents: get_tooltip

     contents: function (d, defaultTitleFormat, defaultValueFormat, color) {

         var $$ = this, config = $$.config,
         titleFormat = config.tooltip_format_title || defaultTitleFormat,
         nameFormat = config.tooltip_format_name || function (name) { return name; },
         valueFormat = config.tooltip_format_value || defaultValueFormat,
         text, i, title, value, name, bgcolor;
         for (i = 0; i < d.length; i++) {
            if (!(d[i] && (d[i].value || d[i].value === 0))) { continue; }
            if (!text) {

                A="assignment Name";
                M ="Points"
               // title = titleFormat ? titleFormat(d[i].x) : d[i].x;
               title = this.config.data_json[d[0].index].session_name;
               text = "<table class='" + $$.CLASS.tooltip + "' >" + (title || title === 0 ? "<tr><th style='background-color:#02435c;' colspan = '2'>Session: "+ title +"</th></tr>"+"<tr><th>" + A + "</th><th>" + M + "</th></tr>" : "");
            }
            
            bgcolor = $$.levelColor ? $$.levelColor(d[i].value) : color(d[i].id);
            text += "<tr class='" + $$.CLASS.tooltipName + "-" + d[i].id + "'>";
            var submittedOrPending = d[0]['name']; //points_available or points _earned
            

            if (submittedOrPending=='points_available'){
                for(i = 0; i<(this.config.data_json[d[0].index].available_assignments).length; i++){
                text += "<td class='name'><span style='background-color:" + bgcolor + "'></span>" + this.config.data_json[d[0].index].available_assignments[i]['Assignment'] + "</td>";
                text += "<td class='value'>" + this.config.data_json[d[0].index].available_assignments[i]['points_allocated'] + "</td>";
                text += "</tr>";
            }
            }
            else if(submittedOrPending == 'points_earned'){
                for(i = 0; i<(this.config.data_json[d[0].index].earnings).length; i++){
                text += "<td class='name'><span style='background-color:" + bgcolor + "'></span>" + this.config.data_json[d[0].index].earnings[i]['assignment'] + "</td>";
                text += "<td class='value'>" + this.config.data_json[d[0].index].earnings[i]['points_earned'] + "</td>";
                text += "</tr>";
            }
            }
            
         }
         return text + "</table>";
      }
    }
    
});
}




