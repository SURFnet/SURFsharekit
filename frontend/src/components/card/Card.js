import React from "react";
import './card.scss'

function Card(props) {

    let cardStyle = {
        backgroundColor: props.backgroundColor ? props.backgroundColor : "white"
    }

    return (
        <div className="card" style={cardStyle}>
            <div className="content-wrapper">
                {props.content}
            </div>
        </div>
    );
}

export default Card;