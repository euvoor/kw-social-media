import { LoadingButton } from "@mui/lab"
import {
  Alert,
  Box,
  Card,
  CardContent,
  CircularProgress,
  Stack,
  Typography,
} from "@mui/material"
import React, { useEffect, useState } from "react"
import { useSelector } from "react-redux"
import ConnectorField from "~/components/ConnectorField"
import accountApi from "../../services/account"

function Account() {
  const modulePath = useSelector(state => state.core.module.path)
  const { data, error, isLoading, isFetching } = accountApi.useGetAccountQuery({
    prefix: modulePath,
  }, {
    skip: !modulePath,
  })
  const [ updateAccount, { isLoading: isSaving } ] = accountApi.useUpdateAccountMutation()
  const [ publishingConnection, setPublishingConnection ] = useState(null)
  const [ saveError, setSaveError ] = useState("")
  const [ saved, setSaved ] = useState(false)

  useEffect(() => {
    if (!data) return

    setPublishingConnection(data.data?.publishing_connection || null)
  }, [ data ])

  async function save() {
    setSaveError("")
    setSaved(false)

    try {
      await updateAccount({
        prefix: modulePath,
        body: {
          publishing_connection: publishingConnection,
        },
      }).unwrap()
      setSaved(true)
    } catch (requestError) {
      setSaveError(
        requestError?.data?.errors?.publishing_connection?.[0]
          || requestError?.data?.message
          || "Unable to save the publishing account.",
      )
    }
  }

  if (isLoading && !data) {
    return (
      <Box sx={{ p: 3 }}>
        <CircularProgress size={24} />
      </Box>
    )
  }

  return (
    <Box sx={{ px: { xs: 2, sm: 3, md: 4 }, py: { xs: 3, md: 4 } }}>
      <Card variant="outlined" sx={{ maxWidth: 760, mx: "auto" }}>
        <CardContent sx={{ p: { xs: 2, sm: 3 } }}>
          <Stack spacing={2.5}>
            <Stack spacing={0.75}>
              <Typography variant="h5" sx={{ fontWeight: 700 }}>
                Publishing account
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Choose the connected LinkedIn account SocialMedia may use for future publishing.
                This setting does not publish, schedule, or read analytics.
              </Typography>
            </Stack>

            {error && (
              <Alert severity="error">
                Unable to load eligible publishing accounts.
              </Alert>
            )}

            {saveError && <Alert severity="error">{saveError}</Alert>}
            {saved && <Alert severity="success">Publishing account saved.</Alert>}

            <ConnectorField
              id="publishing_connection"
              label="LinkedIn account"
              required
              value={publishingConnection}
              catalog={data?.catalog}
              disabled={isFetching || isSaving}
              onChange={(_id, value) => {
                setPublishingConnection(value)
                setSaved(false)
              }}
              helperText={data?.catalog?.connections?.length === 0
                ? "No eligible connected LinkedIn account is available."
                : "Requires the Publish posts capability."}
            />

            <LoadingButton
              variant="contained"
              loading={isSaving}
              disabled={!publishingConnection || isFetching}
              onClick={save}
              data-testid="social-media-save-connection"
              sx={{ alignSelf: "flex-start" }}
            >
              Save account
            </LoadingButton>
          </Stack>
        </CardContent>
      </Card>
    </Box>
  )
}

export default React.memo(Account)
