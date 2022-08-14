var chart = c3.generate({
    bindto: '#lineChart',
    data: {
        columns: [
            ['Available', 20, 20, 30, 40, 20, 30],
            ['Earned', 15, 20, 15, 35]
        ],
        types: {
            
            Earned: 'line',
            Available: 'bar'
        },
        colors: {
            Available: '#ade2eb',
            Earned: '#02435'
        }
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
        x: {
            show: true,
            label: {
                text: 'Modules',
                position: 'outer-center',
                fontSize: '5px'
            }
        }
    }
});