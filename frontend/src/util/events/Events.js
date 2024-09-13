export class UpdateTaskCountEvent extends Event {
    constructor(data) {
        super("UpdateTaskCountEvent");
        this.data = data
    }
}

export class CollapsePersonMergeFooterEvent extends Event {
    constructor(value) {
        super("CollapsePersonMergeFooterEvent");
        this.data = {value: value}
    }
}

export class CopyMetaFieldValueEvent extends Event {
    constructor(key, value) {
        super("CopyMetaFieldValueEvent");
        this.data = {key: key, value: value}
    }
}

export class SetCopiedMetaField extends Event {
    constructor(key, value) {
        super("SetCopiedMetaField");
        this.data = {key: key, value: value}
    }
}