import React from "react";
import './horizontaltablist.scss'

function HorizontalTabList(props) {

    return <div className="horizontal-tab-list">
        {
            props.tabsTitles.map((title, i) => {
                    const marginClassAddition = i === 0 ? '' : ' with-margin'
                    const activeClassAddition = i === props.selectedIndex ? 'active' : ''

                    return <div className={"horizontal-tab " + marginClassAddition} onClick={() => props.onTabClick(i)} key={i}>
                        <div className={"tab-title " + activeClassAddition}>
                            {title}
                        </div>
                        <div className={"tab-underline " + activeClassAddition}/>
                    </div>
                }
            )
        }
    </div>
}

export default HorizontalTabList