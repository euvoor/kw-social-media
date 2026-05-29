import React from "react"
import { Box, Stack, Typography } from "@mui/material"

function Home() {
  return (
    <Box sx={{ px: { xs: 2, sm: 3 }, py: { xs: 3, sm: 4 } }}>
      <Stack spacing={1}>
        <Typography variant="h5" sx={{ fontWeight: 700 }}>
          SocialMedia
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Start building the module pages, explorers, and webapi flows here.
        </Typography>
      </Stack>
    </Box>
  )
}

export default React.memo(Home)
