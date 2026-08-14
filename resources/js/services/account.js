import { createApi, retryFetchBaseQueryApi } from "~/adapters/rtk/query"

const accountApi = createApi({
  reducerPath: "rtk.social_media.account",
  baseQuery: retryFetchBaseQueryApi({
    baseUrl: "/",
  }),
  tagTypes: [ "SocialMediaAccount" ],
  endpoints: build => ({
    getAccount: build.query({
      query: params => ({
        url: `${params.prefix}/webapi/account/connector`,
        method: "GET",
      }),
      providesTags: [ "SocialMediaAccount" ],
    }),
    updateAccount: build.mutation({
      query: params => ({
        url: `${params.prefix}/webapi/account/connector`,
        method: "PUT",
        body: params.body,
      }),
      invalidatesTags: [ "SocialMediaAccount" ],
    }),
  }),
})

export default accountApi
