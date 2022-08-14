
if (typeof data1 !== 'undefined') {
    var chartDivId = "progressChart"
    var colorOfCompleatedDots = '#02435C'
    var colorOfCurrentDots = '#D76F5F'
    var colorOfPendingDots = '#C0C0C0'

    var clientWidth = document.getElementById(chartDivId).clientWidth;

    var width = clientWidth;
    var margin = clientWidth * 0.1;


    var dotCount = data1.length;
    var dotRadiusToLineLengthRatio = 8;

    var tooltip = d3.select('#tooltip');

// 2*dotCount*radiusOfDot//space taken by dots
// 3*radiusOfDot*(dotCount-1)//space taken by lines
    var radiusOfDot = (width - margin) / (2 * dotCount + dotRadiusToLineLengthRatio * (dotCount - 1));

    var height = 10 * radiusOfDot;
// var height = 280;
    var lengthOfOneLine = dotRadiusToLineLengthRatio * radiusOfDot;
    var diameterOfDot = 2 * radiusOfDot;
// radiusOfDot*((2*dotCount+3*(dotCount-1)) = width-margin

// radiusOfDot*2*dotcount + radiusOfDot*3*(dotCount-1)=width-margin

    var x1 = margin / 2 + radiusOfDot;
// var y1 = height/2;
    var y1 = 2 * radiusOfDot

    var svg = d3.select('#' + chartDivId).append('svg')
            .attr('width', width)
            .attr('height', height);
    // .style('border','3px solid red')


    circleAndText('text')
}

function circleAndText(className) {

    var dotCx = x1;
    var dotCy = y1;
    for (var i = 0; i < data1.length; i++) {

        var colorOfDot = colorOfPendingDots;
        if (i < countOfItemsCompleted) {
            colorOfDot = colorOfCompleatedDots;
        } else if (i == countOfItemsCompleted) {
            colorOfDot = colorOfCurrentDots;
        }

        if (dotCx > x1) {
            svg.append('line')
                    .attr('x1', dotCx - radiusOfDot - lengthOfOneLine)
                    .attr('y1', dotCy)
                    .attr('x2', dotCx - radiusOfDot)
                    .attr('y2', dotCy)
                    .attr('stroke', colorOfDot)
                    .attr('stroke-width', '2px');
        }


        svg.append('circle')
                // .attr('cx',60+(x+1)*50)
                .attr('cx', dotCx)
                .attr('cy', dotCy)
                .attr('fill', colorOfDot)
                .attr('r', radiusOfDot + 'px')
                .attr('id', i)

                .on("mouseover", function () {

                    var dotIndex = this.id;

                    tooltip.style("left", d3.select(this).attr("cx") + "px");
                    // tooltip.style("left", d3.event.pageX-230 + "px");
                    tooltip.style("top", d3.select(this).attr("cy") - 50 + "px");
                    // tooltip.style("top", d3.event.pageY-750 + "px");
                    tooltip.style("display", "inline-block");
                    tooltip.select('span').text(toolTipTextArray[dotIndex]);

                    tooltip.style("opacity", 1)
                })

                .on("mouseout", function () {
                    tooltip.style("display", "none");
                })
        // .attr('r',13)


        // if(i%2==0 ){
        svg.append('text')
                .attr('class', className)
                .attr('x', dotCx)
                .attr('y', dotCy + diameterOfDot * 1.5)
                .attr('text-anchor', 'middle')
                .text(data1[i])
                .call(wrap, lengthOfOneLine);
        // }
        // else {
        // 	svg.append('text')
        // 	.attr('class',className)
        // 	.attr('x',x1+i*(diameterOfDot+lengthOfOneLine))
        // 	.attr('y',(y1-diameterOfDot)-(diameterOfDot*0.5))
        // 	.attr('text-anchor','middle')
        // 	.text(data1[i])		
        // 	.call(wrap,lengthOfOneLine*0.8);
        // }

        dotCx += lengthOfOneLine + diameterOfDot;

    }
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