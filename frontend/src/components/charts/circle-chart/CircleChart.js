import React from 'react';
import {PieChart, Pie, Sector, Cell, ResponsiveContainer, Tooltip} from 'recharts';
import './circle-chart.scss';

const CircleChart = (props) => {

    const { data, width, height } = props;

    const CustomTooltip = ({ active, payload, label }) => {
        if (active && payload && payload.length) {
            return (
                <div className="custom-tooltip">
                    <p className="label">{`${payload[0].value}`}</p>
                </div>
            );
        }

        return null;
    };

    return (
        <PieChart width={width} height={height}>
            <Pie
                data={data}
                isAnimationActive={false}
                cx={width / 2}
                cy={height / 2}
                cornerRadius={100}
                innerRadius={100}
                outerRadius={120}
                fill="#8884d8"
                paddingAngle={-20}
                dataKey="value"
                stroke={"transparent"}
            >
                {data.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={props.colors[index]}  style={{
                        outline: "none"
                    }}/>
                ))}
            </Pie>
            <Tooltip content={<CustomTooltip />}/>
        </PieChart>
    )
};

export default CircleChart;