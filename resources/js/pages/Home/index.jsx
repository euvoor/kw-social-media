import React from "react"
import { Box, Card, CardContent, Stack, Typography } from "@mui/material"

function Home() {
  return (
    <Box sx={{ px: { xs: 2, sm: 3 }, py: { xs: 3, sm: 4 } }}>
      <Card variant="outlined" sx={{ maxWidth: 760, mx: "auto" }}>
        <CardContent sx={{ p: { xs: 2, sm: 3 } }}>
          <Stack spacing={1}>
            <Typography variant="h5" sx={{ fontWeight: 700 }}>
              SocialMedia
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Connect an eligible LinkedIn account from the Account page. Publishing, scheduling,
              analytics, and content management are intentionally outside this module slice.
            </Typography>
          </Stack>
        </CardContent>
      </Card>
    </Box>
  )
}

export default React.memo(Home)
