import { retryFetchBaseQueryApi } from "~/adapters/rtk/query"

export default function(opts = {}) {
  return retryFetchBaseQueryApi({
    baseUrl: "__MODULE_PREFIX__/webapi",
    ...opts,
  })
}
