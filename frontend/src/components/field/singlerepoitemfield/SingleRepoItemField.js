import React, {useEffect, useRef} from "react";
import './singlerepoIitemfield.scss'

export function SingleRepoItemField(props) {
    const hiddenInputPersonRef = useRef();

    useEffect(() => {
        props.register({name:props.name})
        props.setValue(props.name, JSON.stringify(props.relatedRepoItem ?? props.defaultValue))
    }, []);

    function getRepoItemTitle() {
        if(props.relatedRepoItem) {
            return props.relatedRepoItem.title
        } else if(props.defaultValue && props.defaultValue.summary) {
            return props.defaultValue.summary.title
        }
        return null
    }

    return (
        <div className={"field"}>
            <input type="hidden" ref={hiddenInputPersonRef} name={props.name}
                   value={props.defaultValue ? undefined : JSON.stringify(props.defaultValue)}/>
            {getRepoItemTitle()}
        </div>
    )
}