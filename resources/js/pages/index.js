import React from "react"
import { Suspense } from "~/components"

const Home = React.lazy(() => import("./Home"))
const Account = React.lazy(() => import("./Account"))

export default {
  "Home": () => <Suspense component={<Home />} />,
  "Account": () => <Suspense component={<Account />} />,
}
