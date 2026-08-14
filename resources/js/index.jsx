import React from "react"
import { createRoot } from "react-dom/client"
import App from "~/App"
import createStore from "~/store"
import reducer from "./index.slice"
import pages from "./pages"
import components from "./components"
import accountApi from "./services/account"

const reducers = {
  module: reducer,
  [accountApi.reducerPath]: accountApi.reducer,
}

const middlewares = [ accountApi.middleware ]

function Module() {
  return []
}


const container = document.getElementById("root")
const root = createRoot(container)

root.render(
  <App
    store={createStore(reducers, middlewares)}
    module={() => <Module />}
    modulePages={pages}
    moduleComponents={components}
  />
)
