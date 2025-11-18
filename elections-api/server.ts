import express, {type Request, type Response} from "express";
import {fileURLToPath} from "url";
import path from "path";
import {deployElectionsContract, type DeployOptions,} from "./deploy.js";
import {
    type ActionOptions,
    closeElection,
    getWalletStatus,
    openElection,
    registerCandidate,
    type RegisterCandidateOptions,
    vote,
    type VoteOptions,
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

type RegisterCandidateRequestBody = ActionRequestBody & {
    candidateId?: unknown;
};

type VoteRequestBody = ActionRequestBody & {
    ballot?: unknown;
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

const parseRegisterCandidateOptions = (
    body: RegisterCandidateRequestBody | undefined
): RegisterCandidateOptions => {
    const baseOptions = parseActionOptions(body);

    if (!body || typeof body !== "object") {
        throw new Error("Request body is required");
    }

    if (typeof body.candidateId !== "string" || body.candidateId.length === 0) {
        throw new Error("candidateId is required and must be a non-empty string");
    }

    return {
        ...baseOptions,
        candidateId: body.candidateId,
    };
};

const hexStringToUint8Array = (hex: string): Uint8Array => {
    const cleanHex = hex.startsWith("0x") ? hex.slice(2) : hex;
    if (cleanHex.length !== 64) {
        throw new Error("Hex string must be 32 bytes (64 hex characters)");
    }
    return new Uint8Array(
        cleanHex.match(/.{1,2}/g)!.map(byte => parseInt(byte, 16))
    );
};

const parseVoteOptions = (
    body: VoteRequestBody | undefined
): VoteOptions => {
    const baseOptions = parseActionOptions(body);

    if (!body || typeof body !== "object") {
        throw new Error("Request body is required");
    }

    if (!body.ballot || typeof body.ballot !== "object") {
        throw new Error("ballot is required and must be an object");
    }

    const ballot = body.ballot as any;

    // Validate and parse ballot structure
    if (!ballot.election_id || !ballot.candidate_id || !ballot.credential) {
        throw new Error("ballot must contain election_id, candidate_id, and credential");
    }

    // Convert hex strings to Uint8Arrays
    const parsedBallot = {
        election_id: typeof ballot.election_id === "string"
            ? hexStringToUint8Array(ballot.election_id)
            : ballot.election_id,
        candidate_id: typeof ballot.candidate_id === "string"
            ? hexStringToUint8Array(ballot.candidate_id)
            : ballot.candidate_id,
        credential: {
            subject: {
                id: typeof ballot.credential.subject.id === "string"
                    ? hexStringToUint8Array(ballot.credential.subject.id)
                    : ballot.credential.subject.id,
                first_name: typeof ballot.credential.subject.first_name === "string"
                    ? hexStringToUint8Array(ballot.credential.subject.first_name)
                    : ballot.credential.subject.first_name,
                last_name: typeof ballot.credential.subject.last_name === "string"
                    ? hexStringToUint8Array(ballot.credential.subject.last_name)
                    : ballot.credential.subject.last_name,
                national_identifier: typeof ballot.credential.subject.national_identifier === "string"
                    ? hexStringToUint8Array(ballot.credential.subject.national_identifier)
                    : ballot.credential.subject.national_identifier,
                birth_timestamp: typeof ballot.credential.subject.birth_timestamp === "string"
                    ? BigInt(ballot.credential.subject.birth_timestamp)
                    : ballot.credential.subject.birth_timestamp,
            },
            signature: {
                pk: {
                    x: typeof ballot.credential.signature.pk.x === "string"
                        ? BigInt(ballot.credential.signature.pk.x)
                        : ballot.credential.signature.pk.x,
                    y: typeof ballot.credential.signature.pk.y === "string"
                        ? BigInt(ballot.credential.signature.pk.y)
                        : ballot.credential.signature.pk.y,
                },
                R: {
                    x: typeof ballot.credential.signature.R.x === "string"
                        ? BigInt(ballot.credential.signature.R.x)
                        : ballot.credential.signature.R.x,
                    y: typeof ballot.credential.signature.R.y === "string"
                        ? BigInt(ballot.credential.signature.R.y)
                        : ballot.credential.signature.R.y,
                },
                s: typeof ballot.credential.signature.s === "string"
                    ? BigInt(ballot.credential.signature.s)
                    : ballot.credential.signature.s,
            },
        },
    };

    return {
        ...baseOptions,
        ballot: parsedBallot,
    };
};

export const createApp = () => {
    const app = express();
    app.use(express.json({limit: "1mb"}));

    let deploymentInFlight: Promise<unknown> | null = null;
    let actionInFlight: Promise<unknown> | null = null;

    app.get("/health", (_req: Request, res: Response) => {
        res.json({status: "ok"});
    });

    app.get("/wallet-status", async (_req: Request, res: Response) => {
        try {
            const status = await getWalletStatus();
            res.json(status);
        } catch (error) {
            console.error("GET /wallet-status failed:", error);
            const message =
                error instanceof Error ? error.message : "Failed to get wallet status";
            res.status(500).json({error: message});
        }
    });

    app.post("/deploy", async (req: Request, res: Response) => {
        if (deploymentInFlight) {
            res
                .status(409)
                .json({error: "Another deployment is currently running."});
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
            res.status(500).json({error: message});
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
                .json({error: "Another contract interaction is currently running."});
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
            res.status(500).json({error: message});
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

    app.post("/register", async (req: Request, res: Response) => {
        if (actionInFlight) {
            res
                .status(409)
                .json({error: "Another contract interaction is currently running."});
            return;
        }

        try {
            const options = parseRegisterCandidateOptions(req.body as RegisterCandidateRequestBody);
            actionInFlight = registerCandidate(options);

            const result = await actionInFlight;
            res.status(201).json(result);
        } catch (error) {
            console.error("POST /register failed:", error);
            const message =
                error instanceof Error ? error.message : "Register candidate failed";
            res.status(500).json({error: message});
        } finally {
            actionInFlight = null;
        }
    });

    app.post("/vote", async (req: Request, res: Response) => {
        if (actionInFlight) {
            res
                .status(409)
                .json({error: "Another contract interaction is currently running."});
            return;
        }

        try {
            const options = parseVoteOptions(req.body as VoteRequestBody);
            actionInFlight = vote(options);

            const result = await actionInFlight;
            res.status(201).json(result);
        } catch (error) {
            console.error("POST /vote failed:", error);
            const message =
                error instanceof Error ? error.message : "Vote failed";
            res.status(500).json({error: message});
        } finally {
            actionInFlight = null;
        }
    });

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
