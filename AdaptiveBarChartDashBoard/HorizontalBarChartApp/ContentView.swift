//
//  ContentView.swift
//  HorizontalBarChartApp
//
//  Created by Nivarti Jayaram on 02/12/22.
//

import SwiftUI

struct ContentView: View {
    var body: some View {
        GeometryReader{
            geometry in
            VStack(alignment: .leading, spacing:0){
                Text("Weekly Sales").font(.title).padding()
                
                let rowHeight = geometry.size.height / CGFloat(weekSales.count)
                let labelWidth = geometry.size.width * 0.09
                let graphWidth = geometry.size.width * 0.6
                let valueWidth = geometry.size.width * 0.18
                ForEach(weekSales){
                    weekData in
                    HStack{
                        Text(weekData.weekDay)
                            .font(.caption)
                            .bold()
                        let rowWidth = calculateRowWidth(graphWidth,weekData)
                        
                        Rectangle().cornerRadius(5).padding(.vertical,5).frame(maxWidth:.infinity).frame(maxWidth:rowWidth,maxHeight: .infinity,alignment: .leading).foregroundColor(Color.blue)
                        Text(formatSales(weekData)).font(.caption2).frame(maxWidth:valueWidth,maxHeight: .infinity,alignment: .leading)
                    }
                    .padding(.horizontal)
                    .frame(maxHeight:rowHeight)
                }
            }
            .padding(.vertical)
        }
    }
    func formatSales(_ weekData: WeekData)-> String{
        return String(format:"$%.2f",weekData.sales)
    }
    func calculateRowWidth(_ graphWidth:Double, _ weekData: WeekData) ->Double{
        let size = weekData.sales / largestSales() * graphWidth
        return size.isNaN ? 0.0 : size
    }
}

struct ContentView_Previews: PreviewProvider {
    static var previews: some View {
        ContentView()
    }
}
