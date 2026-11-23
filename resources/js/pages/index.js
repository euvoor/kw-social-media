import React from "react"
import { Suspense } from "~/components"

const Home = React.lazy(() => import("./Home"))

export default {
  "Home": () => <Suspense component={<Home />} />,
}
