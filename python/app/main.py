from fastapi import FastAPI
from .api.health import router as health_router

app = FastAPI(title="MoonVPN API", version="0.1.0")

app.include_router(health_router)


