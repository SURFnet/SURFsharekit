import React, {useEffect, useMemo} from "react";
import {EditorContent, useEditor} from "@tiptap/react";
import StarterKit from "@tiptap/starter-kit";
import Underline from "@tiptap/extension-underline";
import Link from "@tiptap/extension-link";
import {Markdown} from "@tiptap/markdown";
import {validateDependencyKeyGroup} from "../../../util/DependencyKeyValidation";
import "./richtTextEditor.scss";
import {ReactComponent as IconBold} from "../../../resources/icons/markdown-editor-icons/ic-bold.svg";
import {ReactComponent as IconItalic} from "../../../resources/icons/markdown-editor-icons/ic-italic.svg";
import {ReactComponent as IconUnderline} from "../../../resources/icons/markdown-editor-icons/ic-underline.svg";
import {ReactComponent as IconList} from "../../../resources/icons/markdown-editor-icons/ic-icon-list.svg";
import {ReactComponent as IconLink} from "../../../resources/icons/markdown-editor-icons/ic-link.svg";

const ToolbarButton = ({ onClick, isActive, children }) => {
    return (
        <button
            type="button"
            onMouseDown={(e) => e.preventDefault()}
            onClick={onClick}
            className={`toolbar-btn ${isActive ? "is-active" : ""}`}
        >
            {children}
        </button>
    );
}

export function RichTextEditor({readonly, defaultValue, onChange, register, name, setValue, isRequired, validationRegex, dependencyKey, dependencyGroupKeys, dependencyGroupLabels, getValues, isValid, hasError}) {
    const initialValue = useMemo(() => defaultValue, [defaultValue]);

    function validateRequired(value) {
        return !(isRequired && !value);
    }

    useEffect(() => {
        if (!register || !name) return;

        register(name, {
            required: isRequired,
            validate: (v) => {
                if (!validateRequired(v)) return false;
                return validateDependencyKeyGroup({
                    dependencyKey,
                    dependencyGroupKeys,
                    dependencyGroupLabels,
                    getValues
                });
            }
        });
    }, [register, name, isRequired, validationRegex, dependencyKey, dependencyGroupKeys, dependencyGroupLabels, getValues]);

    const editor = useEditor({
        editable: !readonly,
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                heading: false,
                orderedList: false,
                blockquote: false,
                code: false,
                codeBlock: false,
                horizontalRule: false,
                hardBreak: false,
                strike: false,
            }),
            Underline,
            Link.configure({
                openOnClick: false,
                autolink: true,
                HTMLAttributes: {
                    rel: "noopener noreferrer",
                    target: "_blank",
                },
            }),
            Markdown,
        ],
        content: initialValue,
        shouldRerenderOnTransaction: true,
        onUpdate: ({ editor: currentEditor }) => {
            const markdownValue = currentEditor.getMarkdown();

            onChange?.(markdownValue);

            if (setValue && name) {
                setValue(name, markdownValue, {
                    shouldValidate: false
                });
            }
        },
    });

    useEffect(() => {
        if (!editor) return;
        editor.setEditable(!readonly);
    }, [editor, readonly]);

    useEffect(() => {
        if (!editor || initialValue === undefined) return;

        const currentMarkdown = editor.getMarkdown();

        if (currentMarkdown !== initialValue) {
            editor.commands.setContent(initialValue, {
                emitUpdate: false,
                contentType: "markdown"
            });
        }

        if (setValue && name) {
            setValue(name, initialValue, { shouldDirty: false, shouldValidate: false });
        }
    }, [editor, initialValue, setValue, name]);

    if (!editor) return null;

    const toggleLink = () => {
        if (editor.isActive("link")) {
            if (editor.state.selection.empty) {
                editor.chain().focus().unsetMark("link", { extendEmptyMarkRange: false }).run();
            } else {
                editor.chain().focus().unsetLink().run();
            }
            return;
        }

        const previousUrl = editor.getAttributes("link").href || "";
        const url = window.prompt("Enter URL", previousUrl || "https://");

        if (url === null) return;
        if (url.trim() === "") {
            editor.chain().focus().unsetLink().run();
            return;
        }

        editor.chain().focus().extendMarkRange("link").setLink({ href: url.trim() }).run();
    };

    return (
        <div className="richtext-editor-field">
            {!readonly && (
                <div className="rte-toolbar">
                    <ToolbarButton onClick={() => editor.chain().focus().toggleBold().run()} isActive={editor.isActive("bold")}>
                        <IconBold />
                    </ToolbarButton>
                    <ToolbarButton onClick={() => editor.chain().focus().toggleItalic().run()} isActive={editor.isActive("italic")}>
                        <IconItalic />
                    </ToolbarButton>
                    <ToolbarButton onClick={() => editor.chain().focus().toggleUnderline().run()} isActive={editor.isActive("underline")}>
                        <IconUnderline />
                    </ToolbarButton>
                    <div className="toolbar-divider" />
                    <ToolbarButton onClick={() => editor.chain().focus().toggleBulletList().run()} isActive={editor.isActive("bulletList")}>
                        <IconList />
                    </ToolbarButton>
                    <ToolbarButton onClick={toggleLink} isActive={editor.isActive("link")}>
                        <IconLink />
                    </ToolbarButton>
                </div>
            )}

            <div
                className={`field-input text-area rte-content ${readonly ? "readonly" : ""} ${hasError ? "invalid" : ""} ${isValid ? "valid" : ""}`}
                onClick={() => {
                    if (!readonly) {
                        editor.chain().focus().run();
                    }
                }}
            >
                <EditorContent editor={editor} />
            </div>

            <input type="hidden" name={name} defaultValue={initialValue}/>
        </div>
    );
}