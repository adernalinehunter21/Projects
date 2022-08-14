
if (typeof data !== 'undefined' && data !== null) {
    var noOfLegendsPerRow = 2
    var pointChartDivId = "pointsChart"
    var noOfLegendsPerRow = 2;
    var clientWidth = document.getElementById(pointChartDivId).clientWidth;
    var clientHeight = document.getElementById(pointChartDivId).clientHeight;

    clientHeight = (clientHeight == 0) ? (clientWidth / 0.9) : clientHeight;

    var chartContainerHeight = clientHeight * 0.9;//70% of the available height for the chart
    var dimentionOfChartContainer = Math.min(clientWidth, chartContainerHeight);

    var margin = dimentionOfChartContainer * 0.2;//5% of the dimension is the margin on each side

    var heightOfSpaceForLegends = dimentionOfChartContainer * 0.1;

    var width = dimentionOfChartContainer - 2 * margin;

    var height = width;
    var radius = width / 2;
    centerX = width / 2 + margin;
    centerY = height / 2;

    var totalAngle = 2 * Math.PI
    var radiusOfInnerCircle = radius / 2

    var widthOfOneArc = (radius - (radiusOfInnerCircle)) / data.length

    var tooltip1 = d3.select('#tooltip1');

// var tooltip1 = d3.select('body').append("div")
//     .attr("class", "tooltip")


    var whiteCircle = widthOfOneArc * 0.5

    var innerRadius = (radius - (widthOfOneArc - whiteCircle / 2))
// var innerRadius = (radius - widthOfOneArc)+whiteCircle/2
    var outerRadius = radius

    var svg = d3.select('#' + pointChartDivId)
            .append('svg')
            .attr('width', dimentionOfChartContainer)
            .attr('height', dimentionOfChartContainer - 2 * margin + heightOfSpaceForLegends)

    /*
     
     This for loop is responsible for calling the drawDonut function
     here in the arguments startAngle is 0, and 
     for the endAngle I have just calculated the value with respect to the totalAngle
     
     */

    for (var i = 0; i < data.length; i++) {


        // var whiteCircle = widthOfOneArc*0.5
        // drawDonut(data,innerRadius,outerRadius,'none',0,totalEndAngle)
        var startAngle = -(Math.PI / 2);
        var endAngle = startAngle + ((data[i].value) / (data[i].outOf)) * totalAngle;
        // var tooltipText = data[i].label+<br />+((data[i].value/data[i].outOf)*100)+' %'
        // var tooltipText = ((data[i].value / data[i].outOf) * 100) + ' %'
        var tooltipText = data[i].tooltipText
        drawDonut(data, innerRadius, outerRadius, data[i].color, 0.1, tooltipText, startAngle, totalAngle)
        drawDonut(data, innerRadius, outerRadius, data[i].color, 1, tooltipText, startAngle, endAngle)

        var outerRadius = (innerRadius - (whiteCircle) / 2)
        var innerRadius = (innerRadius - widthOfOneArc)



        // var outerRadius = innerRadius-(innerRadius*0.05) 
        // var innerRadius = innerRadius-(innerRadius*0.15)

    }


// circle(radiusOfInnerCircle, '#D8D8D8')
    circle(radiusOfInnerCircle, 'none')

    var cxForLegend = margin;
    var cyForLegend = dimentionOfChartContainer - 1.9 * margin;
    var widthForEachLegend = dimentionOfChartContainer / noOfLegendsPerRow;
    var widthOfLegendRect = widthForEachLegend * 0.2;
    var heightOfLegendRect = widthForEachLegend * 0.1;
    var widthForLegendText = (widthForEachLegend * 0.9) - widthOfLegendRect;

    for (var i = 1; i <= data.length; i++) {

        legend(cxForLegend, cyForLegend, widthOfLegendRect, heightOfLegendRect, widthForLegendText, data[i - 1].color, data[i - 1].label);

        cxForLegend += widthForEachLegend;
        if (cxForLegend > dimentionOfChartContainer) {
            cxForLegend = margin;
            cyForLegend += 20;
        }

    }
}

/*
 Draw circles inside the the arcs
 */

function circle(circleRadius, color, textInside) {
    var g = svg.append('g')
            .attr('transform', 'translate(' + (centerX) + ',' + (centerY) + ')')

    var circle = g.append('circle')
            .attr('cx', 0)
            .attr('cy', 0)
            .attr('r', circleRadius)
            .attr('fill', color)

    g.append('line')
            .attr('x1', -circleRadius + (circleRadius / 2))
            .attr('y1', 0)
            .attr('x2', circleRadius - (circleRadius / 2))
            .attr('y2', 0)
            .attr('stroke', 'black')
            .attr('stroke-width', '1px')

    g.append('text')
            .attr('x', 0)
            .attr('y', 0)
            .attr('text-anchor', 'middle')
            .attr('dy', -10)
            .attr('font-size', '20px')
            .text(numeratorTextElements[0] + '/' + numeratorTextElements[1])
            .attr('fill', 'grey')
    g.append('text')
            .attr('x', 0)
            .attr('y', 0)
            .attr('text-anchor', 'middle')
            .attr('dy', 20)
            .attr('font-size', '20px')
            .text(denominatorTextElements)
            .attr('fill', 'grey')

}

/*This function is used to draw donuts */

function drawDonut(data, innerRadius, outerRadius, color, opacity, tooltipText, startAngle, endAngle) {

    var g = svg.append('g')
            .attr('transform', 'translate(' + (centerX) + ',' + (centerY) + ')')

    var arc = d3.arc()
            .innerRadius(innerRadius)
            .outerRadius(outerRadius)
            .startAngle(startAngle)
            .endAngle(endAngle)

    var path = g.selectAll('path')
            .data(data)
            .enter().append('path')
            .attr('class', 'arc')
            .attr('d', arc)
            .attr('fill', color)
            .attr('opacity', opacity)

            .on("mouseover", function () {
                var divOffset = $('#' + pointChartDivId).offset();
                var divPosition = d3.event;

                tooltip1.style("left", (divPosition.pageX - divOffset.left) + "px");
                tooltip1.style("top", (divPosition.pageY - divOffset.top) + "px");
                tooltip1.style("display", "inline-block");
                tooltip1.select('span').text(tooltipText);

                tooltip1.style("opacity", 1)
            })

            .on("mouseout", function () {
                tooltip1.style("display", "none");
            })


}

function legend(x, y, width, height, textWidth, color, text) {

    var g = svg.append('g')
            .attr('transform', 'translate(' + (x) + ',' + (y) + ')')

    g.append('rect')
            .attr('x', 0)
            .attr('y', 0)
            .attr('width', width)
            .attr('height', height)
            .attr('fill', color)

    g.append('text')
            .attr('x', width * 1.2)
            .attr('y', height / 2)
            .text(text)
            .attr('font-size', '12px')
            .call(wrap, 90)
}




function wrap(text, width) {
    text.each(function () {
        var text = d3.select(this),
                words = text.text().split(/\s+/).reverse(),
                word,
                line = [],
                lineNumber = 0,
                lineHeight = 1.1, // ems
                x = text.attr("x"),
                y = text.attr("y"),
                dy = 0, //parseFloat(text.attr("dy")),
                tspan = text.text(null)
                .append("tspan")
                .attr("x", x)
                .attr("y", y)
                .attr("dy", dy + "em");
        while (word = words.pop()) {
            line.push(word);
            tspan.text(line.join(" "));
            if (tspan.node().getComputedTextLength() > width) {
                line.pop();
                tspan.text(line.join(" "));
                line = [word];
                tspan = text.append("tspan")
                        .attr("x", x)
                        .attr("y", y)
                        .attr("dy", ++lineNumber * lineHeight + dy + "em")
                        .text(word);
            }
        }
    });
}
