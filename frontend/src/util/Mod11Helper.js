export class Mod11Helper {
    static mod11Validator(value) {
        let values = value.split('')

        let checkDigit = values.pop()

        let digits = values.map(Number)

        let sum = 0
        digits.reverse()
        digits.forEach((v, i) => {
            let mod = 2 + (i % 8) // NCR algorithm
            sum += mod * v
        })
        let rem = sum % 11
        let check = 0
        if (rem !== 0) {
            if (rem === 1) {
                check = 'X'
            } else {
                check = 11 - rem
            }
        }
        return check == checkDigit;
    }

    static mod11_2Validator(value) {
        value = value.replace(/[^0-9X]/g, ''); //remove all non-digits
        let values = value.split('')

        let checkDigit = values.pop()

        let digits = values.map(Number)

        let sum = 0
        digits.forEach((v, i) => {
            sum = (sum + v) * 2
        })
        let remainder = sum % 11
        let result = (12 - remainder) % 11
        if (result === 10) {
            result = 'X'
        }
        return result == checkDigit;
    }
}