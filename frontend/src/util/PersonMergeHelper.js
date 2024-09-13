class PersonMergeHelper {

    static getAllUniqueValuesForField(listOfProfileData, fieldName) {
        if(listOfProfileData) {
            const listOfValues = listOfProfileData.map(profile => {
                return profile[fieldName]
            })
            return [...new Set(listOfValues)].filter(Boolean)
        }
        return []
    }

    static getAllOptionsForField(listOfProfileData, fieldName) {
        if(listOfProfileData) {
            const listOfUniqueValues = this.getAllUniqueValuesForField(listOfProfileData, fieldName)
            return listOfUniqueValues.map(value => {
                return {
                    value: value,
                    labelNL: value,
                    labelEN: value
                }
            })
        }
        return []
    }
}

export default PersonMergeHelper;