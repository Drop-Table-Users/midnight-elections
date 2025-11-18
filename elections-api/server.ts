import express, { type Request, type Response } from "express";
import { fileURLToPath } from "url";
import path from "path";
import {
  deployElectionsContract,
  type DeployOptions,
} from "./deploy.js";
import {
  openElection,
  closeElection,
  type ActionOptions,
} from "./actions.js";
import * as config from "./config.js";

type DeployRequestBody = {
  walletSeed?: unknown;
  electionId?: unknown;
};

type ActionRequestBody = {
  walletSeed?: unknown;
  contractAddress?: unknown;
};

const parseDeployOptions = (
  body: DeployRequestBody | undefined
): DeployOptions => {
  const options: DeployOptions = {
    walletSeed: config.WALLET_SEED,
    electionId: config.ELECTION_CONSTRUCTOR_ARGS._election_id,
  };

  if (!body || typeof body !== "object") {
    return options;
  }

  if (typeof body.walletSeed === "string" && body.walletSeed.length > 0) {
    options.walletSeed = body.walletSeed;
  }

  if (typeof body.electionId === "string" && body.electionId.length > 0) {
    options.electionId = body.electionId;
  }

  return options;
};

const parseActionOptions = (
  body: ActionRequestBody | undefined
): ActionOptions => {
  const options: ActionOptions = {
    walletSeed: config.WALLET_SEED,
  };

  if (!body || typeof body !== "object") {
    return options;
  }

  if (typeof body.walletSeed === "string" && body.walletSeed.length > 0) {
    options.walletSeed = body.walletSeed;
  }

  if (
    typeof body.contractAddress === "string" &&
    body.contractAddress.length > 0
  ) {
    options.contractAddress = body.contractAddress;
  }

  return options;
};

export const createApp = () => {
  const app = express();
  app.use(express.json({ limit: "1mb" }));

  let deploymentInFlight: Promise<unknown> | null = null;
  let actionInFlight: Promise<unknown> | null = null;

  app.get("/health", (_req: Request, res: Response) => {
    res.json({ status: "ok" });
  });

  app.post("/deploy", async (req: Request, res: Response) => {
    if (deploymentInFlight) {
      res
        .status(409)
        .json({ error: "Another deployment is currently running." });
      return;
    }

    const options = parseDeployOptions(req.body as DeployRequestBody);

    deploymentInFlight = deployElectionsContract(options);

    try {
      const result = await deploymentInFlight;
      res.status(201).json(result);
    } catch (error) {
      console.error("POST /deploy failed:", error);
      const message =
        error instanceof Error ? error.message : "Deployment failed";
      res.status(500).json({ error: message });
    } finally {
      deploymentInFlight = null;
    }
  });

  const handleAction = async (
    actionName: "open" | "close",
    actionFn: (options: ActionOptions) => Promise<unknown>,
    req: Request,
    res: Response
  ) => {
    if (actionInFlight) {
      res
        .status(409)
        .json({ error: "Another contract interaction is currently running." });
      return;
    }

    const options = parseActionOptions(req.body as ActionRequestBody);
    actionInFlight = actionFn(options);

    try {
      const result = await actionInFlight;
      res.status(201).json(result);
    } catch (error) {
      console.error(`POST /${actionName} failed:`, error);
      const message =
        error instanceof Error ? error.message : "Interaction failed";
      res.status(500).json({ error: message });
    } finally {
      actionInFlight = null;
    }
  };

  app.post("/open", (req: Request, res: Response) =>
    handleAction("open", openElection, req, res)
  );

  app.post("/close", (req: Request, res: Response) =>
    handleAction("close", closeElection, req, res)
  );

  return app;
};

export const startServer = (port = Number(process.env.PORT) || 3000) => {
  const app = createApp();
  return app.listen(port, () => {
    console.log(`Elections API listening on port ${port}`);
  });
};

const isDirectRun =
  process.argv[1] &&
  path.resolve(process.argv[1]) === fileURLToPath(import.meta.url);

if (isDirectRun) {
  startServer();
}
