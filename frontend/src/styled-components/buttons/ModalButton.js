import React, {useEffect, useRef, useState} from 'react';
import styled from "styled-components";
import {useOutsideElementClicked} from "../../util/hooks/useOutsideElementClicked";
import {useInsideElementClicked} from "../../util/hooks/useInsideElementClicked";

/**
 * With this component you can create a button that shows a model.
 * You can provide your own button and modal components. The rest
 * */

export const MODAL_ALIGNMENT = {
    LEFT : "left: 0;",
    RIGHT : "right: 0;"
}

export const MODAL_VERTICAL_ALIGNMENT = {
    TOP : "top: 0;",
    BOTTOM : "bottom: 0;"
}

function ModalButton(props) {

    const {
        button,
        modal,
        modalHorizontalAlignment, // Determines whether the modal is aligned with the left or the right side of the button
        modalVerticalAlignment, // Determines whether the modal is display on top or beneath the button
        onModalVisibilityChanged,
        modalButtonSpacing, // Space between button and modal; default: 10px
    } = props

    const _modelButtonSpacing = modalButtonSpacing ?? 10

    const modalRef = useRef()
    const buttonRef = React.createRef()
    const [isModalVisible, setModalVisibility] = useState(false);
    const [modalTranslationY, setModalTranslationY] = useState(0)

    const [outsideElementClickedDisabled, setOutsideElementClickedDisabled] = useState(false);

    useOutsideElementClicked(() => {
        if(!outsideElementClickedDisabled) {
           setModalVisibility(false)
        }
        setOutsideElementClickedDisabled(false)
    }, modalRef);

    useEffect(() => {
        if (modalRef.current) {
            setModalTranslationY(modalRef.current.clientHeight)
        }
    }, [modalRef.current])

    useInsideElementClicked(() => {
        if(isModalVisible) {
            setOutsideElementClickedDisabled(true)
        }
        setModalVisibility(!isModalVisible)
    }, buttonRef)

    useEffect(() => {
        onModalVisibilityChanged && onModalVisibilityChanged(isModalVisible)
    }, [isModalVisible])

    // Here, the provided model is cloned so extra props can be added, a function to close the provided model for example :)
    const clonedModal = React.cloneElement(modal, {
        modalButtonClose : () => {
            setModalVisibility(false)
        }
    })

    return (
        <ModalButtonRoot>
            <ButtonWrapper ref={buttonRef}>
                {button}
            </ButtonWrapper>

                <ModalWrapper
                    isModalVisible={isModalVisible}
                    ref={modalRef}
                    translationY={getTranslationY()}
                    alignment={modalHorizontalAlignment}
                    verticalAlignment={modalVerticalAlignment}
                >
                    {clonedModal}
                </ModalWrapper>

        </ModalButtonRoot>
    )

    function getTranslationY() {
        if (modalVerticalAlignment === MODAL_VERTICAL_ALIGNMENT.TOP) {
            return `-${(modalTranslationY + _modelButtonSpacing)}px`
        } else {
            return `${(modalTranslationY) + _modelButtonSpacing}px`
        }
    }
}

const ModalButtonRoot = styled.div`
    position: relative;
`;

const ButtonWrapper = styled.div`
`;

const ModalWrapper = styled.div`
    position: absolute;
    transform: ${props => props.translationY ? `translateY(${props.translationY})` : undefined};
    z-index: 100;
    visibility: ${props => props.isModalVisible ? "visible" : "hidden"};
 
    ${props => props.alignment}
    ${props => props.verticalAlignment}
`;

export default ModalButton;