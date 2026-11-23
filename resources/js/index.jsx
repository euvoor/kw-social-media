import React from "react"
import { createRoot } from "react-dom/client"
import App from "~/App"
import createStore from "~/store"
import reducer from "./index.slice"
import pages from "./pages"
import components from "./components"

const reducers = {
  module: reducer,
}

function Module() {
  return []
}

const container = document.getElementById("root")
const root = createRoot(container)

root.render(
  <App
    store={createStore(reducers)}
    module={() => <Module />}
    modulePages={pages}
    moduleComponents={components}
  />
)
