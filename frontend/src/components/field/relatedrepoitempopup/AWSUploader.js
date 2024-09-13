import Axios from 'axios'
import axiosRetry from 'axios-retry';
import Api from "../../../util/api/Api";
import Toaster from "../../../util/toaster/Toaster";
import {useRef} from "react";

//partsUrls = [{partNumber : 1, url : "http://amazon.com/somemoreofthepresigneduploadurl"}]

export function calculateChunkSize(fileSize) {
    // AWS S3 minimum chunk size is 5 MiB, max is 5 GiB
    const minChunkSize = 1024 * 1024 * 5
    const maxChunkSize = 1024 * 1024 * 50
    const maxChunkAmount = 50 // Limit can be exceeded when the file is larger than 50 * maxChunkSize in bytes (2,5 GiB)

    if (fileSize < minChunkSize) {
        return fileSize
    }

    let chunkSize = Math.ceil(fileSize / maxChunkAmount)

    return Math.max(minChunkSize, Math.min(chunkSize, maxChunkSize));
}

export async function uploadParts(file, chunkSize, partUrls, onPartCompleted, cancelTokenRef) {
    const maxBatchSize = 5
    const axios = Axios.create()
    axiosRetry(axios, {retries: 2, retryDelay: axiosRetry.exponentialDelay});
    delete axios.defaults.headers.put['Content-Type']

    let allResParts = [];
    let promiseBatch = [];
    let partNumber = 0;

    for (let i = 0; i < partUrls.length; i++) {
        const start = i * chunkSize
        const end = (i + 1) * chunkSize
        const blob = i < partUrls.length
            ? file.slice(start, end)
            : file.slice(start)

        const config = {
            headers: {
                "X-Proxy-Url": partUrls[i].proxyUrl
            },
            cancelToken: cancelTokenRef.current.token
        }

        promiseBatch.push(axios.put(partUrls[i].url, blob, config).finally(onPartCompleted))

        // Execute batch if it has reached its max size and reset afterward
        if (promiseBatch.length >= maxBatchSize || i === partUrls.length - 1) {
            const batchResParts = await Promise.all(promiseBatch)
            batchResParts.forEach((resPart, index) => {
                partNumber += 1;
                allResParts.push({
                    eTag: resPart.headers.etag,
                    partNumber: partNumber
                })
            })
            promiseBatch = [];
        }
    }

    return allResParts;
}

// etagsPerParts = [{eTag = "hbtyhtfwelfojrgiler", partNumber: 1}]
export async function finishUpload(postData, successCallback, failureCallback) {
    function onSuccess(response) {
        successCallback(response.data);
    }

    function onLocalFailure(error) {
        failureCallback(error);
    }

    function onValidate(response) {
    }

    function onServerFailure(error) {
        failureCallback(error);
    }

    Api.post('upload/closeUpload', onValidate, onSuccess, onLocalFailure, onServerFailure, {}, postData)
}