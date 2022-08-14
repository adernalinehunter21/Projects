
if (typeof inputJson !== 'undefined' && inputJson !== null) {
    var noOfData = (inputJson.data).length
    var semiCircularChartDiv = document.getElementById('assignmentsChart');
    var parent = semiCircularChartDiv.parentNode;
    var clientWidth = semiCircularChartDiv.clientWidth;
    var parentDiv = document.getElementById('assignmentsChartCardBody');
    var clientHeight = parentDiv.clientHeight;
    var margin = {top: 0, right: 5, bottom: 0, left: 5},
            width = clientWidth - margin.left - margin.right,
            height = (width / 2);


// var color = d3.scaleOrdinal()
// .range(['#60C8D7', '#2ea5b8', '#385360', '#035a7d', '#02435C']);

    var myColor = d3.scaleLinear().domain([1, noOfData])
            .range([inputJson.colorRange.start, inputJson.colorRange.end])

    var scaleStart = inputJson.scaleStart
    var scaleEnd = inputJson.scaleEnd
    var majorScaleSpan = inputJson.majorScaleSpan
    var minorScaleSpan = inputJson.minorScaleSpan
    var value = inputJson.value

    var radius = height;
    var innerRadius = radius * 0.9
    var outerRadius = radius

    var centerX = (width / 2) + margin.left;
    var centerY = height + margin.top;//Math.max(height + margin.top, (clientHeight + height)/2);

    var scale = d3.scaleLinear().domain([scaleStart, scaleEnd]).range([0, 180]);

    var values = []
    inputJson.data.forEach(d => {
        values.push(d.value)
    })
    var totalValue = values.reduce(function (a, b) {
        return parseInt(a) + parseInt(b);
    }, 0)

    var tooltip2 = d3.select('#tooltip2');

    var svg = d3.select('#assignmentsChart')
            .data([inputJson.data])
            .append('svg')
            .attr('width', clientWidth)
            .attr('height', clientHeight)
    var g = svg.append('g')
            .attr("transform", "translate(" + (centerX) + "," + (centerY) + ")");

    var pie = d3.pie()           //this will create arc data for us given a list of values
            .startAngle(-90 * (Math.PI / 180))
            .endAngle(90 * (Math.PI / 180))
            .padAngle(.005) // some space between slices
            .sort(null) //No! we don't want to order it by size
            // .value(20);  
            .value(d => d.arcWidth)


    drawDonut(inputJson.data, innerRadius, outerRadius, 3)
    var radiousOfCentralCircle = innerRadius * 0.3;

    if (inputJson.enableNeedle == true) {
        drawNeedle(outerRadius, innerRadius, centerX, centerY, value, radiousOfCentralCircle)
    }
//circle(centerX, centerY, radiousOfCentralCircle)

// legend(width,height,centerX,centerY,innerRadius,10,10)



    data1 = []
    majorScale = []
    function calcScale() {
        var i = 0
        while (i * minorScaleSpan <= scaleEnd) {
            data1.push(i * minorScaleSpan)
            i++
        }
    }

    calcScale()


    data1.forEach(d => {
        if (d % majorScaleSpan == 0) {

            majorScale.push(d)

            majorScale.forEach(d1 => {

                var lines = g.selectAll(null)
                        .data(pie(inputJson.data))
                        .enter()
                        .append("g")


                lines.append("line")
                        .attr('x1', function (d) {
                            return Math.cos(d.startAngle - Math.PI / 2) * (innerRadius)

                        })
                        .attr('y1', function (d) {
                            return Math.sin(d.startAngle - Math.PI / 2) * (innerRadius)

                        })
                        .attr('x2', function (d) {
                            return Math.cos(d.startAngle - Math.PI / 2) * (innerRadius - (innerRadius * 0.04))

                        })
                        .attr('y2', function (d) {
                            return Math.sin(d.startAngle - Math.PI / 2) * (innerRadius - (innerRadius * 0.04))

                        })
                        .style('stroke', 'black')
                        .style('stroke-width', 1)


//to display till last arc we have to draw the lines using endAngle


                lines.append("line")
                        .attr('id', function (d, i) {
                            return "text" + i
                        })
                        .attr('x1', function (d) {
                            return Math.cos(d.endAngle - Math.PI / 2) * (innerRadius)

                        })
                        .attr('y1', function (d) {
                            return Math.sin(d.endAngle - Math.PI / 2) * (innerRadius)

                        })
                        .attr('x2', function (d) {
                            return Math.cos(d.endAngle - Math.PI / 2) * (innerRadius - (innerRadius * 0.04))

                        })
                        .attr('y2', function (d) {
                            return Math.sin(d.endAngle - Math.PI / 2) * (innerRadius - (innerRadius * 0.04))

                        })
                        .style('stroke', 'black')
                        .style('stroke-width', 1)

            })
        } else {

            g.selectAll(null)
                    .data(pie(inputJson.data))
                    .enter()
                    .append("line")
                    .attr('x1', function (d) {
                        return Math.cos((d.startAngle + d.endAngle) / 2 - Math.PI / 2) * (innerRadius)

                    })
                    .attr('y1', function (d) {
                        return Math.sin((d.startAngle + d.endAngle) / 2 - Math.PI / 2) * (innerRadius)

                    })
                    .attr('x2', function (d) {
                        return Math.cos((d.startAngle + d.endAngle) / 2 - Math.PI / 2) * (innerRadius - (innerRadius * 0.02))

                    })
                    .attr('y2', function (d) {
                        return Math.sin((d.startAngle + d.endAngle) / 2 - Math.PI / 2) * (innerRadius - (innerRadius * 0.02))

                    })
                    .style('stroke', 'black')
                    .style('stroke-width', 0.5)

        }


    })

    var cumulativeScore = 0;
    g.selectAll(null)
            .data(pie(inputJson.data))
            .enter()
            .append('text')
            .attr("class", "scale")

            .text(d => cumulativeScore +=  parseInt(d.data.arcWidth))
            .data(pie(inputJson.data))
            .attr('x', function (d, i) {
                return (Math.cos(d.endAngle - Math.PI / 2) * (innerRadius - (innerRadius * 0.09)))

            })
            .attr('y', function (d, i) {
                return (Math.sin(d.endAngle - Math.PI / 2) * (innerRadius - (innerRadius * 0.09)))

            })

            .attr("fill", "#737373")
            .attr('font-size', '10px')

            .attr("text-anchor", "middle")




//********************display Run and no of person**********************
    if (inputJson.enableCenterText == true) {
        svg.append('text')
                // .attr('fill','black')
                .attr('text-anchor', 'middle')
                .attr("class", "centerLabel1")
                // .attr('font-size','399px')
                .attr('font-weight', 500)
                .attr('x', centerX)
                .attr('y', centerY - (centerY * 0.12))
                .attr('fill', inputJson.centerText.color)
                .attr('font-size', inputJson.centerText.fontSize)
                .text("Scored")



        svg.append('text')
                // .attr('fill','black')
                .attr('text-anchor', 'middle')
                .attr("class", "centerLabel2")
                // .attr('font-size',20)
                .attr('font-weight', 500)
                .attr('x', centerX)
                .attr('y', centerY - (centerY * 0.05))
                .attr('fill', inputJson.centerText.color)
                .attr('font-size', inputJson.centerText.fontSize)
                .text('(' + value + ' Points)')
    }
}

// ======================================================================================
// create a circle

function circle(centerX, centerY, circleRadius) {
    svg.append('circle')
            .attr('cx', centerX)
            .attr('cy', centerY)
            .attr('r', circleRadius)
            .attr('fill', 'transparent')
}

function drawDonut(data, innerRadius, outerRadius, arcRadius) {
    var arc1 = d3.arc()
            .innerRadius(innerRadius)
            .outerRadius(outerRadius)
            .cornerRadius(arcRadius)

    var path1 = g.selectAll('path')
            .data(pie(data))
            .enter()
            .append('path')
            .attr("d", arc1)
            .attr("fill", function (d, i) {
                return myColor(i);
            })

            .on("mouseover", function (d, i, j) {
                var divOffset = $('#assignmentsChart').offset();
                var divPosition = d3.event;

                if (inputJson.enableTooltip == true) {
                    tooltip2.style("left", (divPosition.pageX - divOffset.left) + "px");
                    tooltip2.style("top", (divPosition.pageY - divOffset.top) + "px");
                    tooltip2.style("display", "inline-block");
                    tooltip2.select('span').text(d.data.label);

                    tooltip2.style("opacity", 1)
                } else {
                    tooltip2.style('display', 'none')
                }
            })

            .on("mouseout", function () {
                tooltip2.style("display", "none");
            })


            .each(function (d, i) {
                //A regular expression that captures all in between the start of a string
                //(denoted by ^) and the first capital letter L
                var firstArcSection = /(^.+?)L/;

                //The [1] gives back the expression between the () (thus not the L as well)
                //which is exactly the arc statement
                var newArc = firstArcSection.exec(d3.select(this).attr("d"))[1];
                //Replace all the comma's so that IE can handle it -_-
                //The g after the / is a modifier that "find all matches rather than
                //stopping after the first match"
                newArc = newArc.replace(/,/g, " ");

                //Create a new invisible arc that the text can flow along
                g.append("path")
                        .attr("class", "hiddenDonutArcs")
                        .attr("id", "number" + i)
                        .attr("d", newArc)
                        .style("fill", "none");

            })


    if (inputJson.enablelabel == true) {
        g.selectAll(".legendOnArc")
                .data(pie(data))
                .enter().append("text")
                .attr("class", "legendOnArc")
                // .attr('x',(d)=>(d.startAngle+d.endAngle)/2 - Math.PI / 2)
                // .attr('x',(d)=>(d.startAngle+d.endAngle)/2 - Math.PI / 2)
                .attr("dy", -(outerRadius - innerRadius) / 3)
                .append("textPath")
                .attr("startOffset", "50%")
                .attr('text-anchor', 'middle')
                // .attr('letter-spacing',2)
                .attr('font-weight', 545)
                .attr('font-size', inputJson.labelText.fontSize)
                .attr("fill", inputJson.labelText.color)
                .attr("xlink:href", function (d, i) {
                    return "#number" + i;
                })
                .text(function (d) {
                    return d.data.label;
                });
    }
}

function drawNeedle(outerRadius, innerRadius, centerX, centerY, value, radiousOfCentralCircle) {
    var needle = svg.selectAll(".needle")
            .data([0])
            .enter()
            .append('line')
            .attr("x1", -1 * radiousOfCentralCircle)
            .attr("y1", 0)
            .attr("x2", -(outerRadius - ((outerRadius - innerRadius) * 2)))
            .attr("y2", 0)
            .classed("needle", true)
            .style("stroke", inputJson.needleProperties.needleColor)
            .attr('stroke-width', inputJson.needleProperties.needleWidth)
            .attr("transform", function (d) {
                return " translate(" + centerX + "," + centerY + ") rotate(" + d + ")"
            });


    svg.selectAll(".needle").data([value])
            .transition()
            // .ease( d3.easeElasticOut )
            .duration(2000)
            .attr("transform", function (d) {
                return "translate(" + centerX + "," + centerY + ") rotate(" + scale(d) + ")"
            });
}
