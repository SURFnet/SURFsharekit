export class ReactTableHelper {
    static concatenateCellValue(cellValue) {
        if(cellValue) {
            if(Array.isArray(cellValue)) {
                return cellValue.join("\n")
            } else {
                return cellValue + "\n"
            }
        }
        return ''
    }
}